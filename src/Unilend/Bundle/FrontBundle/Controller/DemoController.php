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
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Attachment, Bids, Clients, Companies, Loans, ProjectParticipant, Projects, ProjectsComments, ProjectsStatus, Users, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\{AttachmentManager, ProjectManager, ProjectStatusManager};
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
        $template = ['projects' => [
            'borrower' => [],
            'broker'   => [],
            'bids'     => [],
            'loans'    => [],
        ]];

        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        if ($user->isBorrower()) {
            // @todo define criteria for recovering projects
            $template['projects']['borrower'] = $this->groupByStatusAndSort($projectRepository->findBy(['idCompany' => $user->getCompany()]));
        }

        if ($user->isPartner()) {
            // @todo define criteria for recovering projects
            $template['projects']['broker'] = $this->groupByStatusAndSort($projectRepository->findBy(['idCompanySubmitter' => $user->getCompany()]));
        }

        if ($user->isLender()) {
            $wallet           = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($user, WalletType::LENDER);
            $bidRepository    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');
            $projects['bids'] = $bidRepository->findBy(['wallet' => $wallet, 'status' => Bids::STATUS_PENDING], ['added' => 'ASC']);

            $loanRepository    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Loans');
            $loans             = $loanRepository->findBy(['wallet' => $wallet, 'status' => Loans::STATUS_ACCEPTED]);
            $projects['loans'] = [];

            foreach ($loans as $loan) {
                $projects['loans'][$loan->getProject()->getStatus()][] = $loan;
            }

            ksort($projects['loans']);

            $template['projects']['lender'] = $projects;
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
        $products            = [];
        $arrangers           = [];
        $partners            = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->findAll();
        $partnerProducts     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:PartnerProduct')->findBy(['idPartner' => $partners]);
        $companiesRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies');

        foreach ($partnerProducts as $partnerProduct) {
            $productId = $partnerProduct->getIdProduct()->getIdProduct();

            if (false === isset($products[$productId])) {
                $products[$productId] = $translator->trans('product-name_' . $partnerProduct->getIdProduct()->getLabel());
            }
        }

        asort($products);

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
            'products'        => $products,
            'arrangers'       => $arrangers,
            'runs'            => $companiesRepository->findBy(['idCompany' => range(6, 44)], ['name' => 'ASC']),
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
        $borrowerId  = $request->request->get('borrower');
        $title       = $request->request->get('title');
        $description = $request->request->get('description');
        $amount      = $request->request->get('amount');
        $duration    = $request->request->get('duration');
        $date        = $request->request->get('date');
        $date        = $date ? \DateTime::createFromFormat('d/m/Y', $date) : null;
        $partnerId   = $request->request->get('partner');
        $productId   = $request->request->get('product') ?: null;
        $rate        = $request->request->get('rate') ?: null;
        $rate        = $rate ? floatval(str_replace(',', '.', $rate)) : null;
        $guarantee   = $request->request->get('guarantee') ? 1 : 0;
        $arrangerId  = $request->request->get('arranger');
        $runId       = $request->request->get('run');

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
                ->setPeriod($duration)
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

            $this->entityManager->persist($project);
            $this->entityManager->flush($project);

            $projectStatusManager->addProjectStatus(Users::USER_ID_FRONT, $project->getStatus(), $project);

            if ($arrangerId && $arranger = $companiesRepository->find($arrangerId)) {
                $arrangerParticipant = new ProjectParticipant();
                $arrangerParticipant
                    ->setProject($project)
                    ->setCompany($arranger)
                    ->setRoles([ProjectParticipant::COMPANY_ROLE_ARRANGER]);

                $this->entityManager->persist($arrangerParticipant);
                $this->entityManager->flush($arrangerParticipant);
            }

            if ($runId && $run = $companiesRepository->find($runId)) {
                $runParticipant = new ProjectParticipant();
                $runParticipant
                    ->setProject($project)
                    ->setCompany($run)
                    ->setRoles([ProjectParticipant::COMPANY_ROLE_RUN]);

                $this->entityManager->persist($runParticipant);
                $this->entityManager->flush($runParticipant);
            }

            $attachmentTypeRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType');

            foreach ($request->files->all() as $field => $file) {
                $attachmentType = $attachmentTypeRepository->find($request->request->get('filetype')[$field]);
                $attachment     = $attachmentManager->upload($user, $attachmentType, $file, false);
                $filename       = $request->request->get('filename')[$field];

                // @todo "original name" should be used for saving file name, not a label
                $attachment->setOriginalName($filename ?: $attachmentType->getLabel());
                $this->entityManager->flush($attachment);

                $attachmentManager->attachToProject($attachment, $project);
            }

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
     * @param string $hash
     *
     * @return Response
     */
    public function projectDetails(string $hash): Response
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (null === $project) {
            return $this->redirectToRoute('collpub_loans');
        }

        $template = [
            'project'            => $project,
            'product'            => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product')->find($project->getIdProduct()),
            'messages'           => $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsComments')->findBy(['idProject' => $project, 'public' => true], ['added' => 'DESC']),
            'attachmentTypes'    => $this->entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->findAll(),
            'projectAttachments' => $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachment')->findBy(['idProject' => $project], ['added' => 'DESC'])
        ];

        return $this->render(':frontbundle/demo:project_request_details.html.twig', $template);
    }

    /**
     * @Route("/projet/{hash}", name="demo_project_details_form", methods={"POST"}, requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param string            $hash
     * @param Request           $request
     * @param AttachmentManager $attachmentManager
     *
     * @return RedirectResponse
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function projectDetailsForm(string $hash, Request $request, AttachmentManager $attachmentManager, ?UserInterface $user): RedirectResponse
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (null === $project) {
            return $this->redirectToRoute('demo_loans');
        }

        $attachmentTypeRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType');

        foreach ($request->files->all() as $field => $file) {
            $attachmentType = $attachmentTypeRepository->find($request->request->get('files')[$field]);
            $attachment     = $attachmentManager->upload($user, $attachmentType, $file, false);
            $attachmentManager->attachToProject($attachment, $project);
        }

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
            return $this->redirectToRoute('collpub_loans');
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
     * @param Request $request
     * @param string  $hash
     *
     * @return Response
     */
    public function projectUpdate(Request $request, string $hash): Response
    {
        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (false === $project instanceof Projects) {
            return $this->redirectToRoute('demo_loans');
        }

        $field = $request->request->get('name');
        $value = $request->request->get('value', '');

        switch ($field) {
            case 'amount':
                $value = preg_replace('/[^0-9]/', '', $value);
                $project->setAmount($value);
                $value = '120 000';
                break;
        }

        $this->entityManager->flush($project);

        return $this->json([
            'success'  => true,
            'newValue' => $value
        ]);
    }

    /**
     * @Route("/projets", name="demo_projects_list")
     *
     * @param ProjectDisplayManager $projectDisplayManager
     * @param Request               $request
     *
     * @return Response
     */
    public function projectsList(ProjectDisplayManager $projectDisplayManager, Request $request): Response
    {
        $page          = $request->query->get('page', 1);
        $sortDirection = $request->query->get('sortDirection', 'DESC');
        $sortType      = $request->query->get('sortType', 'dateFin');
        $projectStatus = [ProjectsStatus::STATUS_ONLINE, ProjectsStatus::STATUS_FUNDED, ProjectsStatus::STATUS_SIGNED, ProjectsStatus::STATUS_REPAYMENT, ProjectsStatus::STATUS_REPAID, ProjectsStatus::STATUS_LOSS];
        $sort          = ['status' => 'ASC', $sortType => $sortDirection];
        /** @var Projects[] $projects */
        $projects      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy(['status' => $projectStatus], $sort);

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
}
