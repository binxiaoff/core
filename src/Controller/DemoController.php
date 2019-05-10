<?php

namespace Unilend\Controller;

use DateTimeImmutable;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Swift_SwiftException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\{File\UploadedFile, JsonResponse, RedirectResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{AcceptedBids, Attachment, AttachmentType, Bids, Clients, Companies, CompanySector, FeeType, Loans, Partner, PercentFee, Product, Project, ProjectAttachment,
    ProjectAttachmentType, ProjectParticipant, ProjectPercentFee, ProjectStatusHistory, Projects, ProjectsStatus, RepaymentType, UnderlyingContract, Users, Wallet, WalletType};
use Unilend\Form\Lending\BidType;
use Unilend\Form\Project\ProjectAttachmentCollectionType;
use Unilend\Repository\CompaniesRepository;
use Unilend\Repository\{ProjectAttachmentRepository, ProjectAttachmentTypeRepository, ProjectParticipantRepository, ProjectRepository};
use Unilend\Service\Front\ProjectDisplayManager;
use Unilend\Service\WebServiceClient\InseeManager;
use Unilend\Service\{AttachmentManager, DemoMailerManager, ProjectStatusManager};

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
     * @Route("/projet/{hash}", name="demo_project_details", methods={"GET", "POST"}, requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @ParamConverter("project", options={"mapping": {"hash": "hash"}})
     *
     * @param Request                         $request
     * @param Project                         $project
     * @param UserInterface|Clients|null      $user
     * @param CompaniesRepository             $companyRepository
     * @param ProjectRepository               $projectRepository
     * @param ProjectAttachmentRepository     $projectAttachmentRepository
     * @param ProjectAttachmentTypeRepository $projectAttachmentTypeRepository
     * @param AttachmentManager               $attachmentManager
     *
     * @throws Exception
     *
     * @return Response
     */
    public function projectDetails(
        Request $request,
        Project $project,
        ?UserInterface $user,
        CompaniesRepository $companyRepository,
        ProjectRepository $projectRepository,
        ProjectAttachmentRepository $projectAttachmentRepository,
        ProjectAttachmentTypeRepository $projectAttachmentTypeRepository,
        AttachmentManager $attachmentManager
    ): Response {
        $regionalBanks = $companyRepository->findRegionalBanks(['name' => 'ASC']);

        $documentForm = $this->createFormBuilder()
            ->add('projectAttachments', ProjectAttachmentCollectionType::class)
            ->getForm()
        ;

        $documentForm->handleRequest($request);

        if ($documentForm->isSubmitted() && $documentForm->isValid()) {
            $projectAttachmentForms = $documentForm->get('projectAttachments');

            /** @var FormInterface $projectAttachmentForm */
            foreach ($projectAttachmentForms as $projectAttachmentForm) {
                $projectAttachment = $projectAttachmentForm->getData();
                $attachmentForm    = $projectAttachmentForm->get('attachment');
                /** @var Attachment $attachment */
                $attachment = $attachmentForm->getData();
                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $attachmentForm->get('file')->getData();
                $companyOwner = $project->getBorrowerCompany();
                $attachmentManager->upload(null, $companyOwner, $user, null, $attachment, $uploadedFile);
                $project->addProjectAttachment($projectAttachment);
            }

            $projectRepository->save($project);

            return $this->redirect($request->getUri());
        }

        $template = [
            'arrangers'            => $companyRepository->findEligibleArrangers($user->getCompany(), ['name' => 'ASC']),
            'runs'                 => $regionalBanks,
            'regionalBanks'        => $regionalBanks,
            'projectStatus'        => ProjectStatusHistory::getAllProjectStatus(),
            'project'              => $project,
            'attachmentTypes'      => $projectAttachmentTypeRepository->getAttachmentTypes(),
            'projectAttachments'   => $projectAttachmentRepository->getAttachmentsWithoutSignature($project),
            'signatureAttachments' => $projectAttachmentRepository->getAttachmentsWithSignature($project),
            'canChangeBidStatus'   => true,
            'documentForm'         => $documentForm->createView(),
        ];

        return $this->render('demo/project_request_details.html.twig', $template);
    }

    /**
     * @Route(
     *     "/projet/documents/{hash}", name="demo_project_details_upload",
     *     methods={"POST"}, requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"}
     * )
     *
     * @param string                     $hash
     * @param Request                    $request
     * @param AttachmentManager          $attachmentManager
     * @param UserInterface|Clients|null $user
     *
     * @throws Exception
     *
     * @return RedirectResponse
     */
    public function projectDetailsUpload(string $hash, Request $request, AttachmentManager $attachmentManager, ?UserInterface $user): RedirectResponse
    {
        $projectRepository = $this->entityManager->getRepository(Projects::class);
        $project           = $projectRepository->findOneBy(['hash' => $hash]);

        if (null === $project) {
            return $this->redirectToRoute('wallet');
        }

        $this->uploadDocuments($request, $project, $user, $attachmentManager);

        return $this->redirectToRoute('demo_project_details', ['hash' => $project->getHash()]);
    }

    /**
     * @Route("/projet/visibilite/{hash}", name="demo_project_visibility", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"}, methods={"POST"})
     *
     * @param Project             $project
     * @param Request             $request
     * @param CompaniesRepository $companyRepository
     *
     * @return Response
     */
    public function projectVisibility(Project $project, Request $request, CompaniesRepository $companyRepository): Response
    {
        $lenders = $companyRepository->findBy(['idCompany' => $request->request->get('visibility')]);
        $project->setLenders($lenders);

        $this->entityManager->flush();

        return $this->redirectToRoute('demo_project_details', ['hash' => $project->getHash()]);
    }

    /**
     * @Route("/projet/abandon/{hash}", name="demo_project_abandon", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/financement/{hash}", name="demo_project_publish", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/finance/{hash}", name="demo_project_fund", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/signature/{hash}", name="demo_project_sign", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/remboursement/{hash}", name="demo_project_repay", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/rembourse/{hash}", name="demo_project_repaid", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/perte/{hash}", name="demo_project_loss", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     * @param Project                    $project
     * @param ProjectStatusManager       $projectStatusManager
     * @param DemoMailerManager          $mailerManager
     *
     * @throws ConnectionException
     *
     * @return Response
     */
    public function projectStatusUpdate(
        Request $request,
        ?UserInterface $user,
        Project $project,
        ProjectStatusManager $projectStatusManager,
        DemoMailerManager $mailerManager
    ): Response {
        $status = null;
        $route  = $request->get('_route');

        switch ($route) {
            case 'demo_project_abandon':
                $status = ProjectStatusHistory::STATUS_CANCELLED;

                break;
            case 'demo_project_publish':
                $status = ProjectStatusHistory::STATUS_PUBLISHED;

                break;
            case 'demo_project_fund':
                $status = ProjectStatusHistory::STATUS_FUNDED;

                break;
            case 'demo_project_sign':
                $status = ProjectStatusHistory::STATUS_CONTRACTS_REDACTED;

                break;
            case 'demo_project_repay':
                $status = ProjectStatusHistory::STATUS_CONTRACTS_SIGNED;

                break;
            case 'demo_project_repaid':
                $status = ProjectStatusHistory::STATUS_FINISHED;

                break;
            case 'demo_project_loss':
                $status = ProjectStatusHistory::STATUS_LOST;

                break;
        }

        if ($status) {
            $projectStatusManager->addProjectStatus($user, $status, $project);

            switch ($status) {
                case ProjectStatusHistory::STATUS_PUBLISHED:
                    try {
                        $mailerManager->sendProjectPublication($project);
                    } catch (Swift_SwiftException $exception) {
                        $this->logger->error('An error occurred while sending project publication email. Message: ' . $exception->getMessage(), [
                            'class'    => __CLASS__,
                            'function' => __FUNCTION__,
                            'file'     => $exception->getFile(),
                            'line'     => $exception->getLine(),
                        ]);
                    }

                    break;
                case ProjectStatusHistory::STATUS_FUNDED:
                    $this->closeProject($project);

                    try {
                        $mailerManager->sendProjectFundingEnd($project);
                    } catch (Swift_SwiftException $exception) {
                        $this->logger->error('An error occurred while sending project publication email. Message: ' . $exception->getMessage(), [
                            'class'    => __CLASS__,
                            'function' => __FUNCTION__,
                            'file'     => $exception->getFile(),
                            'line'     => $exception->getLine(),
                        ]);
                    }

                    break;
            }
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
                'error'   => 'Invalid parameters',
            ]);
        }

        $companyRepository = $this->entityManager->getRepository(Companies::class);
        $company           = $companyRepository->findOneBy(['siren' => $siren]);

        if (null === $company) {
            $name = $inseeManager->searchSiren($siren);

            if (empty($name)) {
                return $this->json([
                    'success' => false,
                    'error'   => 'Unknown SIREN',
                ]);
            }

            $company = new Companies();
            $company
                ->setSiren($siren)
                ->setName($name)
            ;

            $this->entityManager->persist($company);
            $this->entityManager->flush($company);
        }

        return $this->json([
            'success' => true,
            'id'      => $company->getIdCompany(),
            'name'    => $company->getName(),
            'siren'   => $company->getSiren(),
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
            'name'    => $name,
        ]);
    }

    /**
     * @Route("/projet/update/{hash}", name="demo_project_update", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"}, methods={"POST"})
     *
     * @param Request             $request
     * @param Project             $project
     * @param DemoMailerManager   $mailerManager
     * @param CompaniesRepository $companyRepository
     *
     * @return Response
     */
    public function projectUpdate(Request $request, Project $project, DemoMailerManager $mailerManager, CompaniesRepository $companyRepository): Response
    {
        if (false === $project->isEditable()) {
            return (new Response())
                ->setContent('Le projet a déjà été publié, il ne peut plus être modifié')
                ->setStatusCode(Response::HTTP_FORBIDDEN)
                ;
        }

        $field       = $request->request->get('name');
        $value       = $request->request->get('value', '');
        $outputValue = null;

        switch ($field) {
            case 'title':
                $project->setTitle($value)->setSlug();
                $outputValue = $project->getTitle();

                break;
            case 'response-date':
            case 'closing-date':
                if ($value && 1 === preg_match('#^[0-9]{2}/[0-9]{2}/[0-9]{4}$#', $value)) {
                    $value = DateTimeImmutable::createFromFormat('d/m/Y', $value)->setTime(0, 0, 0);
                } else {
                    $value = null;
                }
                switch ($field) {
                    case 'response-date':
                        $project->setReplyDeadline($value);

                        break;
                    case 'closing-date':
                        $project->setExpectedClosingDate($value);

                        break;
                }

                if ($value) {
                    $outputValue = $value->format('d/m/Y');
                }

                break;
            case 'description':
                $project->setDescription($value);

                $outputValue = $project->getDescription();

                break;
            case 'arranger':
                $arrangerCompany = $companyRepository->find($value);
                $project->setArranger($arrangerCompany);

                $outputValue = $arrangerCompany ? $arrangerCompany->getIdCompany() : null;

                break;
            case 'run':
                $runCompany = $companyRepository->find($value);
                $project->setRun($runCompany);

                $outputValue = $runCompany ? $runCompany->getIdCompany() : null;

                break;
            case 'scoring':
                $value = $value ?: null;
                $project->setInternalRatingScore($value);

                try {
                    $mailerManager->sendScoringUpdated($project);
                } catch (Swift_SwiftException $exception) {
                    $this->logger->error('An error occurred while sending scoring update email. Message: ' . $exception->getMessage(), [
                        'class'    => __CLASS__,
                        'function' => __FUNCTION__,
                        'file'     => $exception->getFile(),
                        'line'     => $exception->getLine(),
                    ]);
                }

                $outputValue = $project->getInternalRatingScore();

                break;
        }
        $this->entityManager->flush();

        return $this->json([
            'success'  => true,
            'newValue' => $outputValue,
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
        $projects = $this->entityManager->getRepository(Projects::class)->findBy(['status' => ProjectDisplayManager::STATUS_DISPLAYABLE], $sort);

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
            'projects'       => $projects,
        ];

        return $this->render('demo/projects_list.html.twig', $template);
    }

    /**
     * @Route("/projets/details/lender/{slug}", name="demo_lender_project_details")
     *
     * @param string                       $slug
     * @param Request                      $request
     * @param UserInterface|Clients|null   $user
     * @param ProjectParticipantRepository $projectParticipantRepository
     * @param ProjectDisplayManager        $projectDisplayManager
     * @param DemoMailerManager            $mailerManager
     *
     * @return Response
     */
    public function projectDetailsForLender(
        string $slug,
        Request $request,
        ?UserInterface $user,
        ProjectParticipantRepository $projectParticipantRepository,
        ProjectDisplayManager $projectDisplayManager,
        DemoMailerManager $mailerManager
    ): Response {
        $project = $this->entityManager->getRepository(Projects::class)->findOneBy(['slug' => $slug, 'status' => ProjectDisplayManager::STATUS_DISPLAYABLE]);

        if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($project, $user)) {
            return $this->redirectToRoute('demo_projects_list');
        }

        $wallet = $this->entityManager->getRepository(Wallet::class)->getWalletByType($user, WalletType::LENDER);
        $bid    = $this->entityManager->getRepository(Bids::class)->findOneBy([
            'wallet'  => $wallet,
            'project' => $project,
            'status'  => [Bids::STATUS_PENDING, Bids::STATUS_ACCEPTED],
        ])
        ;
        $product  = $this->entityManager->getRepository(Product::class)->find($project->getIdProduct());
        $form     = null;
        $arranger = $projectParticipantRepository->findByProjectAndRole($project, ProjectParticipant::COMPANY_ROLE_ARRANGER);
        $run      = $projectParticipantRepository->findByProjectAndRole($project, ProjectParticipant::COMPANY_ROLE_RUN);

        if (null === $bid && $wallet) {
            $bid = new Bids();
            $bid->setProject($project)
                ->setWallet($wallet)
                ->setStatus(Bids::STATUS_PENDING)
            ;
        }

        $form = $this->createForm(BidType::class, $bid);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($bid);
            $this->entityManager->flush();

            try {
                $mailerManager->sendBidSubmitted($bid);
            } catch (Swift_SwiftException $exception) {
                $this->logger->error('An error occurred while sending submitted bid email. Message: ' . $exception->getMessage(), [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__,
                    'file'     => $exception->getFile(),
                    'line'     => $exception->getLine(),
                ]);
            }

            return $this->redirectToRoute('demo_lender_project_details', ['slug' => $project->getSlug()]);
        }

        return $this->render('demo/project.html.twig', [
            'project'            => $project,
            'wallet'             => $wallet,
            'bid'                => $bid,
            'product'            => $product,
            'arranger'           => $arranger,
            'run'                => $run,
            'form'               => $form->createView(),
            'projectAttachments' => $this->entityManager->getRepository(ProjectAttachment::class)->findBy(['idProject' => $project], ['added' => 'DESC']),
        ]);
    }

    /**
     * @Route("/bid/change/status", name="demo_change_bid_status")
     *
     * @param Request           $request
     * @param DemoMailerManager $mailerManager
     *
     * @return JsonResponse
     */
    public function changeBidStatus(Request $request, DemoMailerManager $mailerManager): JsonResponse
    {
        $bidId  = $request->request->get('bid');
        $status = $request->request->get('status');
        $bid    = $this->entityManager->getRepository(Bids::class)->find($bidId);

        if ($bid && in_array($status, $bid->getAllStatus())) {
            $bid->setStatus($status);
            $this->entityManager->flush();

            if (in_array($status, [Bids::STATUS_ACCEPTED, Bids::STATUS_REJECTED])) {
                try {
                    $mailerManager->sendBidAcceptedRejected($bid);
                } catch (Swift_SwiftException $exception) {
                    $this->logger->error('An error occurred while sending accepted/rejected bid email. Message: ' . $exception->getMessage(), [
                        'class'    => __CLASS__,
                        'function' => __FUNCTION__,
                        'file'     => $exception->getFile(),
                        'line'     => $exception->getLine(),
                    ]);
                }
            }
        }

        return $this->json('OK');
    }

    /**
     * @Route("/project/{hash}/fees/add", name="demo_add_project_fees", methods={"POST"}, requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @param string  $hash
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addProjectFees(string $hash, Request $request): JsonResponse
    {
        $projectForm        = $request->request->get('project_type');
        $projectPercentFees = empty($projectForm['projectPercentFees']) ? [] : $projectForm['projectPercentFees'];

        $project = $this->entityManager->getRepository(Projects::class)->findOneBy(['hash' => $hash]);

        if ($project && is_iterable($projectPercentFees)) {
            foreach ($projectPercentFees as $fee) {
                $percentFee = new PercentFee();
                $type       = $this->entityManager->getRepository(FeeType::class)->find($fee['percentFee']['type']);
                $percentFee->setType($type)
                    ->setRate(str_replace(',', '.', $fee['percentFee']['rate']))
                    ->setIsRecurring((bool) empty($fee['percentFee']['isRecurring']) ? false : $fee['percentFee']['isRecurring'])
                ;

                $projectPercentFee = new ProjectPercentFee();
                $projectPercentFee->setPercentFee($percentFee);

                $project->addProjectPercentFee($projectPercentFee);
            }
            $this->entityManager->flush();
        }

        return $this->json('OK');
    }

    /**
     * @Route(
     *     "/project/{hash}/fees/remove/{projectPercentFee}", name="demo_remove_project_fees",
     *     requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"}
     * )
     *
     * @param string            $hash
     * @param ProjectPercentFee $projectPercentFee
     *
     * @return RedirectResponse
     */
    public function removeProjectFees(string $hash, ProjectPercentFee $projectPercentFee): RedirectResponse
    {
        /** @var Projects $project */
        $project = $this->entityManager->getRepository(Projects::class)->findOneBy(['hash' => $hash]);
        if ($project) {
            $project->removeProjectPercentFee($projectPercentFee);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('demo_project_details', ['hash' => $project->getHash()]);
    }

    /**
     * @param Request           $request
     * @param Projects          $project
     * @param Clients           $user
     * @param AttachmentManager $attachmentManager
     *
     * @throws Exception
     */
    private function uploadDocuments(Request $request, Projects $project, Clients $user, AttachmentManager $attachmentManager): void
    {
        $attachmentTypeRepository = $this->entityManager->getRepository(AttachmentType::class);

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
            'pageUrl'           => 'projects',
        ];

        if ($totalPages > 1) {
            $paginationSettings['indexPlan'] = [$page - 1, $page, $page + 1];

            if ($page > $totalPages - 3) {
                $paginationSettings['indexPlan'] = [$totalPages - 3, $totalPages - 2, $totalPages - 1];
            } elseif ($page === $totalPages - 3) {
                $paginationSettings['indexPlan'] = [$totalPages - 4, $totalPages - 3, $totalPages - 2, $totalPages - 1];
            } elseif (4 === $page) {
                $paginationSettings['indexPlan'] = [2, 3, 4, 5];
            } elseif ($page < 4) {
                $paginationSettings['indexPlan'] = [2, 3, 4];
            }
        }

        return $paginationSettings;
    }

    /**
     * @param Project $project
     *
     * @throws ConnectionException
     */
    private function closeProject(Project $project): void
    {
        $bids = $this->entityManager->getRepository(Bids::class)->findBy([
            'project' => $project,
            'status'  => [Bids::STATUS_ACCEPTED, Bids::STATUS_PENDING],
        ])
        ;
        $contract              = $this->entityManager->getRepository(UnderlyingContract::class)->findOneBy(['label' => UnderlyingContract::CONTRACT_BDC]);
        $acceptedBidRepository = $this->entityManager->getRepository(AcceptedBids::class);

        $this->entityManager->getConnection()->beginTransaction();

        try {
            foreach ($bids as $bid) {
                $acceptedBid = $acceptedBidRepository->findOneBy(['idBid' => $bid]);

                if (null !== $acceptedBid) {
                    $this->logger->error('Bid #' . $bid->getIdBid() . ' has already been converted to loan');

                    continue;
                }

                $bid->setStatus(Bids::STATUS_ACCEPTED);

                $loan = new Loans();
                $loan
                    ->setWallet($bid->getWallet())
                    ->setProject($project)
                    ->setIdTypeContract($contract)
                    ->setAmount($bid->getAmount())
                    ->setRate($bid->getRate())
                    ->setStatus(Loans::STATUS_PENDING)
                ;

                $this->entityManager->persist($loan);

                $acceptedBid = new AcceptedBids();
                $acceptedBid
                    ->setIdBid($bid)
                    ->setIdLoan($loan)
                    ->setAmount($bid->getAmount())
                ;

                $this->entityManager->persist($acceptedBid);
                $this->entityManager->flush([$bid, $loan, $acceptedBid]);

                unset($bid, $loan, $acceptedBid);
            }

            $this->entityManager->getConnection()->commit();
        } catch (ConnectionException $exception) {
            $this->entityManager->getConnection()->rollBack();
        }
    }
}
