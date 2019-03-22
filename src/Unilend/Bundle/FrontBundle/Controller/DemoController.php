<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\{JsonResponse, RedirectResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Attachment, Bids, Clients, Companies, FeeType, Loans, Partner, PercentFee, ProjectParticipant, ProjectPercentFee, Projects, ProjectsComments,
    ProjectsStatus, Users, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Repository\ProjectParticipantRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\{AttachmentManager, ProjectManager, ProjectStatusManager};
use Unilend\Bundle\FrontBundle\Form\Lending\BidType;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;
use Unilend\Bundle\WSClientBundle\Service\InseeManager;

/**
 * @Security("is_granted('ROLE_USER')")
 */
class DemoController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * @Route("/portefeuille", name="demo_loans")
     *
     * @param UserInterface|Clients|null $user
     *
     * @return Response
     */
    public function loans(?UserInterface $user): Response
    {
        $template = [
            'projects' => [
                'borrower'  => [],
                'submitter' => [],
                'lender'    => []
            ]
        ];

        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        if ($user->isBorrower()) {
            // @todo define criteria for recovering projects
            $template['projects']['borrower'] = $this->groupByStatusAndSort($projectRepository->findBy(['idCompany' => $user->getCompany()]));
        }

        if ($user->isPartner()) {
            // @todo define criteria for recovering projects
            $template['projects']['submitter'] = $this->groupByStatusAndSort($projectRepository->findBy(['idCompanySubmitter' => $user->getCompany()]));
        }

        if ($user->isLender()) {
            $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($user, WalletType::LENDER);

            // En cours (HOT)
            $projectsInProgressBid = $projectRepository->createQueryBuilder('p')
                ->distinct()
                ->innerJoin('p.bids', 'b')
                ->where('b.wallet = :wallet')
                ->andWhere('p.status = :online')
                ->setParameters(['wallet' => $wallet, 'online' => ProjectsStatus::STATUS_ONLINE])
                ->getQuery()
                ->getResult();

            $projectsInProgressNonSignedLoan = $projectRepository->createQueryBuilder('p')
                ->distinct()
                ->innerJoin('p.loans', 'l')
                ->where('l.wallet = :wallet')
                ->andWhere('l.status = :pending')
                ->setParameters(['wallet' => $wallet, 'pending' => Loans::STATUS_PENDING])
                ->getQuery()
                ->getResult();

            $template['projects']['lender']['inProgress'] = array_merge((array) $projectsInProgressBid, (array) $projectsInProgressNonSignedLoan);

            $inProgressBidCount = $projectRepository->createQueryBuilder('p')
                ->select('count(p)')
                ->distinct()
                ->innerJoin('p.bids', 'b')
                ->where('b.wallet = :wallet')
                ->andWhere('b.status = :pending')
                ->setParameters(['wallet' => $wallet, 'pending' => Bids::STATUS_PENDING])
                ->getQuery()
                ->getSingleScalarResult();

            $inProgressCount = $inProgressBidCount + count($projectsInProgressNonSignedLoan);

            $template['projects']['lender']['inProgressCount'] = [
                'pending' => $inProgressCount,
                'refused' => count($template['projects']['lender']['inProgress']) - $inProgressCount,
            ];

            // Actifs (COLD)
            $projectsActive = $projectRepository->createQueryBuilder('p')
                ->innerJoin('p.loans', 'l')
                ->where('l.wallet = :wallet')
                ->andWhere('l.status = :accepted')
                ->andWhere('p.status in (:active)')
                ->setParameters(['wallet' => $wallet, 'accepted' => Loans::STATUS_ACCEPTED, 'active' => [ProjectsStatus::STATUS_FUNDED, ProjectsStatus::STATUS_SIGNED, ProjectsStatus::STATUS_REPAYMENT]])
                ->getQuery()
                ->getResult();

            $template['projects']['lender']['active'] = $projectsActive;

            // Terminés
            $projectsFinished = $projectRepository->createQueryBuilder('p')
                ->innerJoin('p.loans', 'l')
                ->where('l.wallet = :wallet')
                ->andWhere('l.status = :accepted')
                ->andWhere('p.status in (:finished)')
                ->setParameters(['wallet' => $wallet, 'accepted' => Loans::STATUS_ACCEPTED, 'finished' => [ProjectsStatus::STATUS_LOSS, ProjectsStatus::STATUS_REPAID, ProjectsStatus::STATUS_CANCELLED]])
                ->getQuery()
                ->getResult();

            $template['projects']['lender']['finished'] = $projectsFinished;

            $projectsMasked = $projectRepository->createQueryBuilder('p')
                ->innerJoin('p.loans', 'l')
                ->where('l.wallet = :wallet')
                ->andWhere('l.status = :refused')
                ->setParameters(['wallet' => $wallet, 'refused' => Loans::STATUS_REJECTED])
                ->getQuery()
                ->getResult();

            $template['projects']['lender']['masked'] = $projectsMasked;
        }

        return $this->render(':frontbundle/demo:loans.html.twig', $template);
    }

    /**
     * @param Projects[] $projects
     *
     * @return array
     */
    private function groupByStatusAndSort(array $projects)
    {
        $groupedProjects = [];
        $statuses        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findBy([], ['status' => 'ASC']);

        foreach ($statuses as $status) {
            $groupedProjects[$status->getStatus()] = [];
        }

        foreach ($projects as $project) {
            $groupedProjects[$project->getStatus()][] = $project;
        }

        ksort($groupedProjects);

        return $groupedProjects;
    }

    /**
     * @Route("/depot", name="demo_project_request", methods={"GET"})
     *
     * @param ProjectManager      $projectManager
     * @param TranslatorInterface $translator
     *
     * @return Response
     */
    public function projectRequest(ProjectManager $projectManager, TranslatorInterface $translator): Response
    {
        $arrangers           = [];
        $partners            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->findAll();
        $companiesRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies');

        foreach ($partners as $partner) {
            $company = $partner->getIdCompany();

            if (false === isset($arrangers[$company->getIdCompany()])) {
                $arrangers[$company->getIdCompany()] = $company->getName();
            }
        }

        asort($arrangers);

        $template = [
            'loanPeriods'     => $projectManager->getPossibleProjectPeriods(),
            'partners'        => $partners,
            'products'        => $this->getProductsList($partners, $translator),
            'arrangers'       => $arrangers,
            'runs'            => $companiesRepository->findBy(['idCompany' => range(6, 45)], ['name' => 'ASC']),
            'attachmentTypes' => $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachmentType')->getAttachmentTypes()
        ];

        return $this->render(':frontbundle/demo:project_request.html.twig', $template);
    }

    /**
     * @Route("/depot", name="demo_project_request_form", methods={"POST"})
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     * @param ProjectStatusManager       $projectStatusManager
     * @param AttachmentManager          $attachmentManager
     *
     * @return RedirectResponse
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function projectRequestFormAction(
        Request $request,
        ?UserInterface $user,
        ProjectStatusManager $projectStatusManager,
        AttachmentManager $attachmentManager
    ): RedirectResponse
    {
        $borrowerId         = $request->request->get('borrower');
        $title              = $request->request->get('title');
        $description        = $request->request->get('description');
        $amount             = $request->request->get('amount');
        $duration           = $request->request->get('duration');
        $date               = $request->request->get('date');
        $date               = $date ? \DateTime::createFromFormat('d/m/Y', $date)->setTime(0, 0, 0) : null;
        $partnerId          = $request->request->get('partner');
        $productId          = $request->request->get('product');
        $rate               = $request->request->get('rate');
        $rate               = $rate ? floatval(str_replace(',', '.', $rate)) : null;
        $guarantee          = $request->request->get('guarantee') ? 1 : 0;
        $arrangerId         = $request->request->get('arranger');
        $runId              = $request->request->get('run');
        $projectForm        = $request->request->get('project_type');
        $projectPercentFees = empty($projectForm['projectPercentFees']) ? [] : $projectForm['projectPercentFees'];

        try {
            $this->entityManager->beginTransaction();

            $partner             = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->find($partnerId);
            $companiesRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
            $borrower            = $companiesRepository->find($borrowerId);

            $project = new Projects();
            $project
                ->setIdCompany($borrower)
                ->setTitle($title)
                ->setSlug($this->entityManager->getConnection()->generateSlug($title))
                ->setAmount($amount)
                ->setPeriod($duration * 12)
                ->setComments($description)
                ->setCreateBo(false)
                ->setStatus(ProjectsStatus::STATUS_REQUEST)
                ->setIdPartner($partner)
                ->setIdProduct($productId)
                ->setIdCompanySubmitter($user->getCompany())
                ->setIdClientSubmitter($user)
                ->setDateRetrait($date)
                ->setInterestRate($rate)
                ->setMeansRepayment($guarantee);

            if ($arrangerId && $arranger = $companiesRepository->find($arrangerId)) {
                $project->addArranger($arranger);
            }

            if ($runId && $run = $companiesRepository->find($runId)) {
                $project->addRun($run);
            }

           foreach ($projectPercentFees as $fee) {
               $percentFee = new PercentFee();
               $type = $this->entityManager->getRepository(FeeType::class)->find($fee['percentFee']['type']);
               $percentFee->setType($type)
                   ->setRate(str_replace(',', '.', $fee['percentFee']['rate']))
                   ->setIsRecurring((bool) empty($fee['percentFee']['isRecurring']) ? false : $fee['percentFee']['isRecurring']);

               $projectPercentFee = new ProjectPercentFee();
               $projectPercentFee->setPercentFee($percentFee);

               $project->addProjectPercentFee($projectPercentFee);
           }

            $this->entityManager->persist($project);
            $this->entityManager->flush($project);

            $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, $project->getStatus(), $project);

            $this->uploadDocuments($request, $project, $user, $attachmentManager);

            $this->entityManager->commit();

            return $this->redirectToRoute('demo_project_details', ['hash' => $project->getHash()]);
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();

            $this->logger->error('An error occurred while creating project. Message: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);
        }

        return $this->redirectToRoute('demo_project_request');
    }

    /**
     * @Route("/projet/{hash}", name="demo_project_details", methods={"GET"}, requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param string                     $hash
     * @param ProjectManager             $projectManager
     * @param TranslatorInterface        $translator
     * @param UserInterface|Clients|null $user
     *
     * @return Response
     */
    public function projectDetails(
        string $hash,
        ProjectManager $projectManager,
        TranslatorInterface $translator,
        ?UserInterface $user
    ): Response
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (null === $project) {
            return $this->redirectToRoute('demo_loans');
        }

        /** @var Partner $partner */
        $partner   = $project->getIdPartner();
        $arrangers = [];
        $arranger  = $project->getArrangerParticipant();
        $arranger  = $arranger instanceof ProjectParticipant ? $arranger->getCompany() : null;
        $run       = $project->getRunParticipant();
        $run       = $run instanceof ProjectParticipant ? $run->getCompany() : null;

        $arrangers[$partner->getIdCompany()->getIdCompany()] = $partner->getIdCompany()->getName();
        $arrangers[$user->getCompany()->getIdCompany()]      = $user->getCompany()->getName();

        if ($arranger instanceof Companies) {
            $arrangers[$arranger->getIdCompany()] = $arranger->getName();
        }

        asort($arrangers);

        $companyRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
        $regionalBanks     = $companyRepository->findBy(['idCompany' => range(6, 45)], ['name' => 'ASC']);

        $visibility = [];
        /** @var ProjectParticipant $projectParticipant */
        foreach ($project->getProjectParticipants() as $projectParticipant) {
            if ($projectParticipant->hasRole(ProjectParticipant::COMPANY_ROLE_LENDER)) {
                $visibility[] = $projectParticipant->getCompany()->getIdCompany();
            }
        }

        $projectAttachmentRepository     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachment');
        $projectAttachmentTypeRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachmentType');
        $projectCommentRepository        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsComments');
        $productRepository               = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product');
        $template                        = [
            'loanPeriods'               => $projectManager->getPossibleProjectPeriods(),
            'products'                  => $this->getProductsList([$project->getIdPartner()], $translator),
            'arranger'                  => $arranger,
            'arrangers'                 => $arrangers,
            'run'                       => $run,
            'runs'                      => $regionalBanks,
            'potentialLenders'          => $regionalBanks,
            'visibility'                => $visibility,
            'projectStatus'             => [ProjectsStatus::STATUS_REQUEST, ProjectsStatus::STATUS_REVIEW, ProjectsStatus::STATUS_ONLINE, ProjectsStatus::STATUS_FUNDED, ProjectsStatus::STATUS_SIGNED, ProjectsStatus::STATUS_REPAYMENT, ProjectsStatus::STATUS_REPAID],
            'project'                   => $project,
            'product'                   => $project->getIdProduct() ? $productRepository->find($project->getIdProduct()) : null,
            'messages'                  => $projectCommentRepository->findBy(['idProject' => $project, 'public' => true], ['added' => 'DESC']),
            'attachmentTypes'           => $projectAttachmentTypeRepository->getAttachmentTypes(),
            'projectAttachments'        => $projectAttachmentRepository->findBy(['idProject' => $project], ['added' => 'DESC']),
            'isEditable'                => $projectManager->isEditable($project),
            'isProjectScoringEditable'  => $projectManager->isProjectScoringEditable($project, $user),
            'isBorrowerScoringEditable' => $projectManager->isBorrowerScoringEditable($project, $user)
        ];

        return $this->render(':frontbundle/demo:project_request_details.html.twig', $template);
    }

    /**
     * @Route("/projet/documents/{hash}", name="demo_project_details_upload", methods={"POST"}, requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param string                     $hash
     * @param Request                    $request
     * @param AttachmentManager          $attachmentManager
     * @param UserInterface|Clients|null $user
     *
     * @return RedirectResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function projectDetailsUpload(string $hash, Request $request, AttachmentManager $attachmentManager, ?UserInterface $user): RedirectResponse
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (null === $project) {
            return $this->redirectToRoute('demo_loans');
        }

        $this->uploadDocuments($request, $project, $user, $attachmentManager);

        return $this->redirectToRoute('demo_project_details', ['hash' => $project->getHash()]);
    }

    /**
     * @param Request           $request
     * @param Projects          $project
     * @param Clients           $user
     * @param AttachmentManager $attachmentManager
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function uploadDocuments(Request $request, Projects $project, Clients $user, AttachmentManager $attachmentManager): void
    {
        $attachmentTypeRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType');

        foreach ($request->files->all() as $field => $file) {
            if (false === empty($file)) {
                $attachmentType = $attachmentTypeRepository->find($request->request->get('filetype')[$field]);
                $attachment     = $attachmentManager->upload($user, $attachmentType, $file, false);
                $filename       = $request->request->get('filename')[$field];

                // @todo "original name" should be used for saving file name, not a label
                $attachment->setOriginalName($filename ?: $attachmentType->getLabel());
                $this->entityManager->flush($attachment);

                $attachmentManager->attachToProject($attachment, $project);
            }
        }
    }

    /**
     * @Route("/projet/visibilite/{hash}", name="demo_project_visibility", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return Response
     */
    public function projectVisibility(string $hash, Request $request): Response
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (null === $project) {
            return $this->redirectToRoute('demo_loans');
        }

        $visibility = $request->request->get('visibility');

        /** @var ProjectParticipant $projectParticipant */
        foreach ($project->getProjectParticipants() as $projectParticipant) {
            if (
                $projectParticipant->hasRole(ProjectParticipant::COMPANY_ROLE_LENDER)
                && false === in_array($projectParticipant->getCompany()->getIdCompany(), $visibility)
            ) {
                $roles = $projectParticipant->getRoles();

                unset($roles[array_search(ProjectParticipant::COMPANY_ROLE_LENDER, $roles)]);
                unset($visibility[$projectParticipant->getCompany()->getIdCompany()]);

                if (empty($roles)) {
                    $project->removeProjectParticipants($projectParticipant);
                    $this->entityManager->flush($project);
                } else {
                    $projectParticipant->setRoles($roles);
                    $this->entityManager->flush($projectParticipant);
                }
            } elseif (
                false === $projectParticipant->hasRole(ProjectParticipant::COMPANY_ROLE_LENDER)
                && in_array($projectParticipant->getCompany()->getIdCompany(), $visibility)
            ) {
                $roles   = $projectParticipant->getRoles();
                $roles[] = ProjectParticipant::COMPANY_ROLE_LENDER;

                $projectParticipant->setRoles($roles);
                $this->entityManager->flush($projectParticipant);

                unset($visibility[$projectParticipant->getCompany()->getIdCompany()]);
            }
        }

        $companyRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
        $lenders           = $companyRepository->findBy(['idCompany' => $visibility]);

        $project->addLenders($lenders);

        $this->entityManager->flush($project);

        return $this->redirectToRoute('demo_project_details', ['hash' => $project->getHash()]);
    }

    /**
     * @Route("/projet/chat/{hash}", name="demo_project_chat", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param string                     $hash
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     *
     * @return Response
     */
    public function projectChat(string $hash, Request $request, ?UserInterface $user): Response
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (null === $project) {
            return $this->redirectToRoute('demo_loans');
        }

        $content   = $request->request->filter('content', null, FILTER_SANITIZE_STRING);
        $frontUser = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_FRONT);

        if ($content) {
            $message = new ProjectsComments();
            $message
                ->setIdProject($project)
                ->setIdClient($user)
                ->setContent($content)
                ->setIdUser($frontUser)
                ->setPublic(true);

            $this->entityManager->persist($message);
            $this->entityManager->flush($message);
        }

        $template = [
            'messages' => $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsComments')->findBy(['idProject' => $project, 'public' => true], ['added' => 'DESC'])
        ];

        return $this->render(':frontbundle/demo:project_chat.html.twig', $template);
    }

    /**
     * @Route("/document/{hash}/{idProjectAttachment}", name="demo_project_document", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}", "idAttachment": "\d+"})
     *
     * @param string            $hash
     * @param int               $idProjectAttachment
     * @param AttachmentManager $attachmentManager
     * @param Filesystem        $filesystem
     *
     * @return Response
     */
    public function projectDocument(string $hash, int $idProjectAttachment, AttachmentManager $attachmentManager, Filesystem $filesystem): Response
    {
        $projectRepository           = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project                     = $projectRepository->findOneBy(['hash' => $hash]);
        $projectAttachmentRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachment');
        $projectAttachment           = $projectAttachmentRepository->find($idProjectAttachment);

        if (null === $project || null === $projectAttachment || $project !== $projectAttachment->getProject()) {
            return $this->redirectToRoute('demo_loans');
        }

        /** @var Attachment $attachment */
        $attachment = $projectAttachment->getAttachment();
        $path       = $attachmentManager->getFullPath($attachment);

        if (false === $filesystem->exists($path)) {
            throw new FileNotFoundException(null, 0, null, $path);
        }

        $fileName = $attachment->getOriginalName() ?? basename($attachment->getPath());

        return $this->file($path, $fileName);
    }

    /**
     * @Route("/projet/abandon/{hash}", name="demo_project_abandon", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/revue/{hash}", name="demo_project_review", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/financement/{hash}", name="demo_project_publish", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/finance/{hash}", name="demo_project_fund", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/signature/{hash}", name="demo_project_sign", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/remboursement/{hash}", name="demo_project_repay", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/rembourse/{hash}", name="demo_project_repaid", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/perte/{hash}", name="demo_project_loss", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param Request              $request
     * @param string               $hash
     * @param ProjectStatusManager $projectStatusManager
     *
     * @return Response
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function projectRequestSubmit(Request $request, string $hash, ProjectStatusManager $projectStatusManager): Response
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (false === $project instanceof Projects) {
            return $this->redirect($request->headers->get('referer'));
        }

        $status = null;
        $route  = $request->get('_route');

        switch ($route) {
            case 'demo_project_abandon':
                $status = ProjectsStatus::STATUS_CANCELLED;
                break;
            case 'demo_project_review':
                $status = ProjectsStatus::STATUS_REVIEW;
                break;
            case 'demo_project_publish':
                $status = ProjectsStatus::STATUS_ONLINE;
                break;
            case 'demo_project_fund':
                $status = ProjectsStatus::STATUS_FUNDED;
                break;
            case 'demo_project_sign':
                $status = ProjectsStatus::STATUS_SIGNED;
                break;
            case 'demo_project_repay':
                $status = ProjectsStatus::STATUS_REPAYMENT;
                break;
            case 'demo_project_repaid':
                $status = ProjectsStatus::STATUS_REPAID;
                break;
            case 'demo_project_loss':
                $status = ProjectsStatus::STATUS_LOSS;
                break;
        }

        if ($status) {
            $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, $status, $project);
        }

        return $this->redirectToRoute('demo_project_details', ['hash' => $project->getHash()]);
    }

    /**
     * @Route("/emprunteur", name="demo_borrower_search_form", methods={"POST"})
     *
     * @param Request      $request
     * @param InseeManager $inseeManager
     *
     * @return JsonResponse
     */
    public function borrowerSearchForm(Request $request, InseeManager $inseeManager): JsonResponse
    {
        $siren = $request->request->get('term');

        if (1 !== preg_match('/^[0-9]{9}$/', $siren)) {
            return $this->json([
                'success' => false,
                'error'   => 'Invalid parameters'
            ]);
        }

        $companyRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
        $company           = $companyRepository->findOneBy(['siren' => $siren]);

        if (null === $company) {
            $name = $inseeManager->searchSiren($siren);

            if (empty($name)) {
                return $this->json([
                    'success' => false,
                    'error'   => 'Unknown SIREN'
                ]);
            }

            $company = new Companies();
            $company
                ->setSiren($siren)
                ->setName($name);

            $this->entityManager->persist($company);
            $this->entityManager->flush($company);
        }

        return $this->json([
            'success' => true,
            'id'      => $company->getIdCompany(),
            'name'    => $company->getName(),
            'siren'   => $company->getSiren()
        ]);
    }

    /**
     * @Route("/siren", name="demo_siren_search", methods={"POST"})
     *
     * @param Request      $request
     * @param InseeManager $inseeManager
     *
     * @return JsonResponse
     */
    public function sirenSearch(Request $request, InseeManager $inseeManager): JsonResponse
    {
        $siren = $request->request->get('siren');
        $name  = $siren ? $inseeManager->searchSiren($siren) : null;

        return $this->json([
            'success' => false === empty($name),
            'name'    => $name
        ]);
    }

    /**
     * @Route("/projet/update/{hash}", name="demo_project_update", requirements={"hash":"[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param Request        $request
     * @param string         $hash
     * @param ProjectManager $projectManager
     *
     * @return Response
     */
    public function projectUpdate(Request $request, string $hash, ProjectManager $projectManager): Response
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (false === $project instanceof Projects) {
            return $this->redirectToRoute('demo_loans');
        }

        if (false === $projectManager->isEditable($project)) {
            return (new Response())
                ->setContent('Le projet a déjà été publié, il ne peut plus être modifié')
                ->setStatusCode(Response::HTTP_FORBIDDEN);
        }

        $field       = $request->request->get('name');
        $value       = $request->request->get('value', '');
        $outputValue = null;

        switch ($field) {
            case 'amount':
                $value = preg_replace('/[^0-9]/', '', $value);
                $project->setAmount($value);
                $this->entityManager->flush($project);

                $formatter   = new \NumberFormatter('fr_FR', \NumberFormatter::DEFAULT_STYLE);
                $outputValue = $formatter->format($project->getAmount());
                break;
            case 'duration':
                $value = (int) $value;
                $project->setPeriod($value * 12);
                $this->entityManager->flush($project);

                $outputValue = $project->getPeriod() / 12;
                break;
            case 'product':
                $product = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product')->find($value);
                $product = $product ? $product->getIdProduct() : null;
                $project->setIdProduct($product);
                $this->entityManager->flush($project);

                $outputValue = $project->getIdProduct();
                break;
            case 'rate':
                if ('' === $value) {
                    $value = null;
                } else {
                    $value = str_replace(',', '.', $value);
                    $value = (float) preg_replace('/[^0-9.]/', '', $value);
                }
                $project->setInterestRate($value);
                $this->entityManager->flush($project);

                $formatter   = new \NumberFormatter('fr_FR', \NumberFormatter::DEFAULT_STYLE);
                $outputValue = $formatter->format($project->getInterestRate());
                break;
            case 'date':
                if ($value && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $value)) {
                    $value = \DateTime::createFromFormat('d/m/Y', $value)->setTime(0, 0, 0);
                } else {
                    $value = null;
                }
                $project->setDateRetrait($value);
                $this->entityManager->flush($project);

                if ($value) {
                    $outputValue = $value->format('d/m/Y');
                }
                break;
            case 'guarantee':
                $project->setMeansRepayment(empty($value) ? 0 : 1);
                $this->entityManager->flush($project);

                $outputValue = $project->getMeansRepayment();
                break;
            case 'description':
                $project->setComments($value);
                $this->entityManager->flush($project);

                $outputValue = $project->getComments();
                break;
            case 'arranger':
                $companyRepository          = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
                $arrangerCompany            = $companyRepository->find($value);
                $currentArrangerParticipant = $project->getArrangerParticipant();

                if ($currentArrangerParticipant && $arrangerCompany !== $currentArrangerParticipant->getCompany()) {
                    $project->removeProjectParticipants($currentArrangerParticipant);
                }

                if ($arrangerCompany && (empty($currentArrangerParticipant) || $arrangerCompany !== $currentArrangerParticipant->getCompany())) {
                    $project->addArranger($arrangerCompany);
                }

                $this->entityManager->flush($project);

                $outputValue = $arrangerCompany ? $arrangerCompany->getIdCompany() : null;
                break;
            case 'run':
                $companyRepository     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies');
                $runCompany            = $companyRepository->find($value);
                $currentRunParticipant = $project->getRunParticipant();

                if ($currentRunParticipant && $runCompany !== $currentRunParticipant->getCompany()) {
                    $project->removeProjectParticipants($currentRunParticipant);
                }

                if ($runCompany && (empty($currentRunParticipant) || $runCompany !== $currentRunParticipant->getCompany())) {
                    $project->addRun($runCompany);
                }

                $this->entityManager->flush($project);

                $outputValue = $runCompany ? $runCompany->getIdCompany() : null;
                break;
            case 'arranger-scoring':
                $value = $value ?: null;
                $project->setNatureProject($value);
                $this->entityManager->flush($project);

                $outputValue = $value;
                break;
            case 'run-scoring':
                $value = $value ?: null;
                $project->setObjectifLoan($value);
                $this->entityManager->flush($project);

                $outputValue = $value;
                break;
        }

        return $this->json([
            'success'  => true,
            'newValue' => $outputValue
        ]);
    }

    /**
     * @Route("/projets", name="demo_projects_list")
     *
     * @param ProjectDisplayManager      $projectDisplayManager
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     *
     * @return Response
     */
    public function projectsList(ProjectDisplayManager $projectDisplayManager, Request $request, ?UserInterface $user): Response
    {
        $page          = $request->query->get('page', 1);
        $sortDirection = $request->query->get('sortDirection', 'DESC');
        $sortType      = $request->query->get('sortType', 'dateFin');
        $sort          = ['status' => 'ASC', $sortType => $sortDirection];
        /** @var Projects[] $projects */
        $projects = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy(['status' => ProjectDisplayManager::STATUS_DISPLAYABLE], $sort);

        foreach ($projects as $index => $project) {
            if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($project, $user)) {
                unset($projects[$index]);
            }
        }

        $template = [
            'sortDirection'  => $sortDirection,
            'sortType'       => $sortType,
            'showSortable'   => true,
            'showPagination' => true,
            'currentPage'    => $page,
            'pagination'     => $this->pagination($projectDisplayManager, $page, 20),
            'projects'       => $projects
        ];

        return $this->render(':frontbundle/demo:projects_list.html.twig', $template);
    }

    /**
     * @Route("/projets/detail/{slug}", name="demo_lender_project_details")
     *
     * @param string                       $slug
     * @param UserInterface|Clients|null   $client
     * @param Request                      $request
     * @param ProjectParticipantRepository $projectParticipantRepository
     * @param ProjectDisplayManager        $projectDisplayManager
     *
     * @return Response
     */
    public function projectDetail(
        string $slug,
        ?UserInterface $client,
        Request $request,
        ProjectParticipantRepository $projectParticipantRepository,
        ProjectDisplayManager $projectDisplayManager
    ): Response
    {
        $project = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findOneBy(['slug' => $slug, 'status' => ProjectDisplayManager::STATUS_DISPLAYABLE]);

        if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($project, $client)) {
            return $this->redirectToRoute('demo_projects_list');
        }

        $wallet  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $bid     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->findOneBy(['wallet' => $wallet, 'project' => $project, 'status' => [Bids::STATUS_PENDING, Bids::STATUS_ACCEPTED]]);
        /** @var Bids[] $bids */
        $bids     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->findBy(['project' => $project, 'status' => [Bids::STATUS_PENDING, Bids::STATUS_ACCEPTED]]);
        $product  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product')->find($project->getIdProduct());
        $form     = null;
        $arranger = $projectParticipantRepository->findByProjectAndRole($project, ProjectParticipant::COMPANY_ROLE_ARRANGER);
        $run      = $projectParticipantRepository->findByProjectAndRole($project, ProjectParticipant::COMPANY_ROLE_RUN);

        if (null === $bid) {
            $bid = new Bids();
            $bid->setProject($project)
                ->setWallet($wallet)
                ->setStatus(Bids::STATUS_PENDING);
        }

        $form = $this->createForm(BidType::class, $bid);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($bid);
            $this->entityManager->flush();
        }

        return $this->render(':frontbundle/demo:project.html.twig', [
            'project'            => $project,
            'wallet'             => $wallet,
            'bid'                => $bid,
            'bids'               => $bids,
            'product'            => $product,
            'arranger'           => $arranger,
            'run'                => $run,
            'form'               => $form->createView(),
            'projectAttachments' => $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachment')->findBy(['idProject' => $project], ['added' => 'DESC']),
        ]);
    }

    /**
     * @Route("/projets/detail/bid/cancel/{bid}", name="demo_project_details_bid_cancellation")
     *
     * @param Bids $bid
     *
     * @return RedirectResponse
     */
    public function cancelBid(Bids $bid): RedirectResponse
    {
        $bid->setStatus(Bids::STATUS_REJECTED);
        $this->entityManager->flush();

        return $this->redirectToRoute('demo_lender_project_details', ['slug' => $bid->getProject()->getSlug()]);
    }

    /**
     * @Route("/reporting", name="demo_statistics")
     *
     * @return Response
     */
    public function reporting(): Response
    {
        return $this->render(':frontbundle/demo:reporting.html.twig');
    }

    /**
     * @param ProjectDisplayManager $projectDisplayManager
     * @param int                   $page
     * @param int                   $limit
     *
     * @return array
     */
    private function pagination(ProjectDisplayManager $projectDisplayManager, int $page, int $limit)
    {
        $totalNumberProjects = $projectDisplayManager->getTotalNumberOfDisplayedProjects(null);
        $totalPages          = $limit ? ceil($totalNumberProjects / $limit) : 1;

        $paginationSettings = [
            'itemsPerPage'      => $limit,
            'totalItems'        => $totalNumberProjects,
            'totalPages'        => $totalPages,
            'currentIndex'      => $page,
            'currentIndexItems' => min($page * $limit, $totalNumberProjects),
            'remainingItems'    => $limit ? ceil($totalNumberProjects - ($totalNumberProjects / $limit)) : 0,
            'pageUrl'           => 'projects'
        ];

        if ($totalPages > 1) {
            $paginationSettings['indexPlan'] = [$page - 1, $page, $page + 1];

            if ($page > $totalPages - 3) {
                $paginationSettings['indexPlan'] = [$totalPages - 3, $totalPages - 2, $totalPages - 1];
            } elseif ($page == $totalPages - 3) {
                $paginationSettings['indexPlan'] = [$totalPages - 4, $totalPages - 3, $totalPages - 2, $totalPages - 1];
            } elseif (4 == $page) {
                $paginationSettings['indexPlan'] = [2, 3, 4, 5];
            } elseif ($page < 4) {
                $paginationSettings['indexPlan'] = [2, 3, 4];
            }
        }

        return $paginationSettings;
    }

    /**
     * @param array               $partners
     * @param TranslatorInterface $translator
     *
     * @return array
     */
    private function getProductsList(array $partners, TranslatorInterface $translator): array
    {
        $partnerProducts = $this->entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProduct')->findBy(['idPartner' => $partners]);

        foreach ($partnerProducts as $partnerProduct) {
            $productId = $partnerProduct->getIdProduct()->getIdProduct();

            if (false === isset($products[$productId])) {
                $products[$productId] = $translator->trans('product-name_' . $partnerProduct->getIdProduct()->getLabel());
            }
        }

        asort($products);

        return $products;
    }
}
