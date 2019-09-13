<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use DateTimeImmutable;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\{EntityManagerInterface, ORMException, OptimisticLockException};
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Swift_SwiftException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\{File\UploadedFile, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Valid;
use Unilend\Entity\{AcceptedBids, Attachment, Bids, CaRegionalBank, Clients, Loans, Project, ProjectStatus};
use Unilend\Form\Bid\PartialBid;
use Unilend\Form\Project\{ConfidentialityEditionType, ProjectAttachmentCollectionType, ProjectFeeTypeCollectionType};
use Unilend\Form\Tranche\TrancheTypeCollectionType;
use Unilend\Repository\{AcceptedBidsRepository, BidsRepository, CaRegionalBankRepository, CompaniesRepository, ProjectAttachmentRepository, ProjectAttachmentTypeRepository,
    ProjectRepository, TrancheRepository};
use Unilend\Security\Voter\ProjectVoter;
use Unilend\Service\{Attachment\AttachmentManager, MailerManager, Project\ProjectImageManager, Project\ProjectStatusManager, User\RealUserFinder};

class EditController extends AbstractController
{
    /**
     * @Route("/projet/{hash}", name="edit_project_details", methods={"GET", "POST"}, requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @IsGranted("edit", subject="project")
     *
     * @param Project                         $project
     * @param Request                         $request
     * @param UserInterface|Clients|null      $user
     * @param CompaniesRepository             $companyRepository
     * @param ProjectRepository               $projectRepository
     * @param ProjectAttachmentRepository     $projectAttachmentRepository
     * @param ProjectAttachmentTypeRepository $projectAttachmentTypeRepository
     * @param ProjectImageManager             $projectImageManager
     * @param CaRegionalBankRepository        $regionalBankRepository
     * @param AttachmentManager               $attachmentManager
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function details(
        Project $project,
        Request $request,
        ?UserInterface $user,
        CompaniesRepository $companyRepository,
        ProjectRepository $projectRepository,
        ProjectAttachmentRepository $projectAttachmentRepository,
        ProjectAttachmentTypeRepository $projectAttachmentTypeRepository,
        ProjectImageManager $projectImageManager,
        CaRegionalBankRepository $regionalBankRepository,
        AttachmentManager $attachmentManager
    ): Response {
        $regionalBanks = $companyRepository->findRegionalBanks(['name' => 'ASC']);

        $trancheForm = null;
        $feesForm    = null;

        if ($project->isEditable()) {
            $trancheForm = $this->buildTrancheForm($project);
            if ($this->handleTrancheForm($trancheForm, $request, $projectRepository)) {
                return $this->redirect($request->getUri());
            }

            $feesForm = $this->buildFeesForm($project);
            if ($this->handleFeesForm($feesForm, $request, $projectRepository)) {
                return $this->redirect($request->getUri());
            }
        }

        $documentForm = $this->buildDocumentForm();
        if ($this->handleDocumentForm($project, $user, $documentForm, $request, $projectRepository, $attachmentManager)) {
            return $this->redirect($request->getUri());
        }

        $partialBidForm = $this->createForm(PartialBid::class, null, [
            'action' => $this->generateUrl('edit_bid_partial'),
        ]);

        $confidentialityForm = $this->createForm(ConfidentialityEditionType::class, $project);
        if ($this->handleConfidentialityForm($confidentialityForm, $request, $projectRepository)) {
            return $this->redirect($request->getUri());
        }

        $imageForm = $this->buildImageForm();
        if ($this->handleImageForm($project, $imageForm, $request, $projectRepository, $projectImageManager)) {
            return $this->redirect($request->getUri());
        }

        $template = [
            'arrangers'                => $companyRepository->findEligibleArrangers($user->getCompany(), ['name' => 'ASC']),
            'runs'                     => $regionalBanks,
            'regionalBanks'            => $regionalBanks,
            'regionalBankIds'          => $regionalBankRepository->getRegionalBankIds(null, ['c.name' => 'ASC']),
            'centerRegionalBankIds'    => $regionalBankRepository->getRegionalBankIds(CaRegionalBank::FRIENDLY_GROUP_CENTER, ['c.name' => 'ASC']),
            'northEastRegionalBankIds' => $regionalBankRepository->getRegionalBankIds(CaRegionalBank::FRIENDLY_GROUP_NORTH_EAST, ['c.name' => 'ASC']),
            'westRegionalBankIds'      => $regionalBankRepository->getRegionalBankIds(CaRegionalBank::FRIENDLY_GROUP_WEST, ['c.name' => 'ASC']),
            'southRegionalBankIds'     => $regionalBankRepository->getRegionalBankIds(CaRegionalBank::FRIENDLY_GROUP_SOUTH, ['c.name' => 'ASC']),
            'projectStatus'            => ProjectStatus::getPossibleStatuses(),
            'project'                  => $project,
            'attachmentTypes'          => $projectAttachmentTypeRepository->getAttachmentTypes(),
            'projectAttachments'       => $projectAttachmentRepository->getAttachmentsWithoutSignature($project),
            'signatureAttachments'     => $projectAttachmentRepository->getAttachmentsWithSignature($project),
            'documentForm'             => $documentForm->createView(),
            'trancheForm'              => $trancheForm ? $trancheForm->createView() : null,
            'feesForm'                 => $feesForm ? $feesForm->createView() : null,
            'partialBidForm'           => $partialBidForm->createView(),
            'imageForm'                => $imageForm->createView(),
            'confidentialityForm'      => $confidentialityForm->createView(),
            'offerVisibilities'        => Project::getAllOfferVisibilities(),
        ];

        return $this->render('project/edit/details.html.twig', $template);
    }

    /**
     * @Route("/projet/visibilite/{hash}", name="edit_project_visibility", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"}, methods={"POST"})
     *
     * @IsGranted("edit", subject="project")
     *
     * @param Project             $project
     * @param Request             $request
     * @param CompaniesRepository $companyRepository
     * @param ProjectRepository   $projectRepository
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function visibility(Project $project, Request $request, CompaniesRepository $companyRepository, ProjectRepository $projectRepository): Response
    {
        $lenders = $companyRepository->findBy(['idCompany' => $request->request->get('visibility')]);
        $project->setLenders($lenders);

        $projectRepository->save($project);

        return $this->redirectToRoute('edit_project_details', ['hash' => $project->getHash()]);
    }

    /**
     * @Route("/projet/abandon/{hash}", name="edit_project_status_abandon", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/finance/{hash}", name="edit_project_status_funded", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/signature/{hash}", name="edit_project_status_contracts_redacted", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/remboursement/{hash}", name="edit_project_status_signed", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/rembourse/{hash}", name="edit_project_status_finished", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/perte/{hash}", name="edit_project_status_lost", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @IsGranted("edit", subject="project")
     *
     * @param Project                    $project
     * @param Request                    $request
     * @param UserInterface|Clients|null $user
     * @param ProjectStatusManager       $projectStatusManager
     * @param MailerManager              $mailerManager
     * @param LoggerInterface            $logger
     * @param TrancheRepository          $trancheRepository
     * @param BidsRepository             $bidsRepository
     * @param AcceptedBidsRepository     $acceptedBidRepository
     * @param EntityManagerInterface     $entityManager
     * @param RealUserFinder             $realUserFinder
     *
     * @throws ConnectionException
     *
     * @return Response
     */
    public function projectStatusUpdate(
        Project $project,
        Request $request,
        ?UserInterface $user,
        ProjectStatusManager $projectStatusManager,
        MailerManager $mailerManager,
        LoggerInterface $logger,
        TrancheRepository $trancheRepository,
        BidsRepository $bidsRepository,
        AcceptedBidsRepository $acceptedBidRepository,
        EntityManagerInterface $entityManager,
        RealUserFinder $realUserFinder
    ): Response {
        $status = null;
        $route  = $request->get('_route');

        switch ($route) {
            case 'edit_project_status_abandon':
                $status = ProjectStatus::STATUS_CANCELLED;

                break;
            case 'edit_project_status_funded':
                $status = ProjectStatus::STATUS_FUNDED;

                break;
            case 'edit_project_status_contracts_redacted':
                $status = ProjectStatus::STATUS_CONTRACTS_REDACTED;

                break;
            case 'edit_project_status_signed':
                $status = ProjectStatus::STATUS_CONTRACTS_SIGNED;

                break;
            case 'edit_project_status_finished':
                $status = ProjectStatus::STATUS_FINISHED;

                break;
            case 'edit_project_status_lost':
                $status = ProjectStatus::STATUS_LOST;

                break;
        }

        if ($status) {
            $projectStatusManager->addProjectStatus($status, $project);

            switch ($status) {
                case ProjectStatus::STATUS_FUNDED:
                    $this->closeProject(
                        $project,
                        $trancheRepository,
                        $bidsRepository,
                        $acceptedBidRepository,
                        $logger,
                        $entityManager,
                        $realUserFinder
                    );

                    try {
                        $mailerManager->sendProjectFundingEnd($project);
                    } catch (Swift_SwiftException $exception) {
                        $logger->error('An error occurred while sending project publication email. Message: ' . $exception->getMessage(), [
                            'class'    => __CLASS__,
                            'function' => __FUNCTION__,
                            'file'     => $exception->getFile(),
                            'line'     => $exception->getLine(),
                        ]);
                    }

                    break;
            }
        }

        return $this->redirectToRoute('edit_project_details', ['hash' => $project->getHash()]);
    }

    /**
     * @Route("/projet/update/{hash}", name="edit_project_update", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"}, methods={"POST"})
     *
     * @IsGranted("edit", subject="project")
     *
     * @param Project                       $project
     * @param Request                       $request
     * @param CompaniesRepository           $companyRepository
     * @param ProjectRepository             $projectRepository
     * @param AuthorizationCheckerInterface $authorizationChecker
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function update(
        Project $project,
        Request $request,
        CompaniesRepository $companyRepository,
        ProjectRepository $projectRepository,
        AuthorizationCheckerInterface $authorizationChecker
    ): Response {
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
            case 'consultation-closing-date':
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
                    case 'consultation-closing-date':
                        $project->setLenderConsultationClosingDate($value);

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
            case 'deputy-arranger':
                $deputyArrangerCompany = $companyRepository->find($value);
                $project->setDeputyArranger($deputyArrangerCompany);

                $outputValue = $deputyArrangerCompany ? $deputyArrangerCompany->getIdCompany() : null;

                break;
            case 'run':
                $runCompany = $companyRepository->find($value);
                $project->setRun($runCompany);

                $outputValue = $runCompany ? $runCompany->getIdCompany() : null;

                break;
            case 'loan-officer':
                $loanOfficerCompany = $companyRepository->find($value);
                $project->setLoanOfficer($loanOfficerCompany);

                $outputValue = $loanOfficerCompany ? $loanOfficerCompany->getIdCompany() : null;

                break;
            case 'security-trustee':
                $securityTrusteeCompany = $companyRepository->find($value);
                $project->setSecurityTrustee($securityTrusteeCompany);

                $outputValue = $securityTrusteeCompany ? $securityTrusteeCompany->getIdCompany() : null;

                break;
            case 'scoring':
                if (false === $authorizationChecker->isGranted(ProjectVoter::ATTRIBUTE_RATE, $project)) {
                    return (new Response())
                        ->setContent('Vous ne disposez pas des droits nécessaires pour modifier la notation. Seul le RUN peut modifier la notation.')
                        ->setStatusCode(Response::HTTP_FORBIDDEN)
                    ;
                }

                $value = $value ?: null;
                $project->setInternalRatingScore($value);

                $outputValue = $project->getInternalRatingScore();

                break;
            case 'offer-visibility':
                $project->setOfferVisibility((int) $value);
                $outputValue = $project->getOfferVisibility();

                break;
        }

        $projectRepository->save($project);

        return $this->json([
            'success'  => true,
            'newValue' => $outputValue,
        ]);
    }

    /**
     * @param Project                $project
     * @param TrancheRepository      $trancheRepository
     * @param BidsRepository         $bidsRepository
     * @param AcceptedBidsRepository $acceptedBidRepository
     * @param LoggerInterface        $logger
     * @param EntityManagerInterface $entityManager
     * @param RealUserFinder         $realUserFinder
     *
     * @throws ConnectionException
     */
    private function closeProject(
        Project $project,
        TrancheRepository $trancheRepository,
        BidsRepository $bidsRepository,
        AcceptedBidsRepository $acceptedBidRepository,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        RealUserFinder $realUserFinder
    ): void {
        $tranches = $trancheRepository->findBy(['project' => $project]);
        $bids     = $bidsRepository->findBy([
            'tranche' => $tranches,
            'status'  => [Bids::STATUS_ACCEPTED, Bids::STATUS_PENDING],
        ]);

        $entityManager->getConnection()->beginTransaction();

        try {
            foreach ($bids as $bid) {
                $acceptedBid = $acceptedBidRepository->findOneBy(['idBid' => $bid]);

                if (null !== $acceptedBid) {
                    $logger->error('Bid #' . $bid->getIdBid() . ' has already been converted to loan');

                    continue;
                }

                $bid->setStatus(Bids::STATUS_ACCEPTED);

                $loan = new Loans();
                $loan
                    ->setLender($bid->getLender())
                    ->setTranche($bid->getTranche())
                    ->setMoney($bid->getMoney())
                    ->setRate($bid->getRate())
                    ->setStatus(Loans::STATUS_PENDING)
                ;

                $entityManager->persist($loan);

                $acceptedBid = new AcceptedBids();
                $acceptedBid
                    ->setBid($bid)
                    ->setLoan($loan)
                    ->setMoney($bid->getMoney())
                    ->setAddedByValue($realUserFinder)
                ;

                $entityManager->persist($acceptedBid);
                $entityManager->flush([$bid, $loan, $acceptedBid]);

                unset($bid, $loan, $acceptedBid);
            }

            $entityManager->getConnection()->commit();
        } catch (Exception $e) {
            $entityManager->getConnection()->rollBack();
        }
    }

    /**
     * @param Project $project
     *
     * @return FormInterface
     */
    private function buildTrancheForm(Project $project): FormInterface
    {
        return $this->get('form.factory')->createNamedBuilder('tranches', FormType::class, $project, ['data_class' => Project::class])
            ->add('tranches', TrancheTypeCollectionType::class, [
                'constraints'   => [new Valid()],
                'entry_options' => ['rate_required' => Project::OPERATION_TYPE_SYNDICATION === (int) $project->getOperationType()],
            ])
            ->getForm()
        ;
    }

    /**
     * @param FormInterface     $trancheForm
     * @param Request           $request
     * @param ProjectRepository $projectRepository
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return bool
     */
    private function handleTrancheForm(FormInterface $trancheForm, Request $request, ProjectRepository $projectRepository): bool
    {
        $trancheForm->handleRequest($request);

        if ($trancheForm->isSubmitted() && $trancheForm->isValid()) {
            $project = $trancheForm->getData();
            $projectRepository->save($project);

            return true;
        }

        return false;
    }

    /**
     * @return FormInterface
     */
    private function buildDocumentForm(): FormInterface
    {
        return $this->get('form.factory')->createNamedBuilder('attachments')
            ->add('projectAttachments', ProjectAttachmentCollectionType::class)
            ->getForm()
        ;
    }

    /**
     * @return FormInterface
     */
    private function buildImageForm(): FormInterface
    {
        return $this->get('form.factory')->createNamedBuilder('image')
            ->add('imageFile', FileType::class, ['constraints' => new Image()])
            ->getForm()
        ;
    }

    /**
     * @param Project           $project
     * @param Clients           $user
     * @param FormInterface     $documentForm
     * @param Request           $request
     * @param ProjectRepository $projectRepository
     * @param AttachmentManager $attachmentManager
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return bool
     */
    private function handleDocumentForm(
        Project $project,
        Clients $user,
        FormInterface $documentForm,
        Request $request,
        ProjectRepository $projectRepository,
        AttachmentManager $attachmentManager
    ): bool {
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

            return true;
        }

        return false;
    }

    /**
     * @param Project $project
     *
     * @return FormInterface
     */
    private function buildFeesForm(Project $project)
    {
        return $this->get('form.factory')->createNamedBuilder('fees', FormType::class, $project, ['data_class' => Project::class])
            ->add('projectFees', ProjectFeeTypeCollectionType::class)
            ->getForm()
        ;
    }

    /**
     * @param FormInterface     $feesForm
     * @param Request           $request
     * @param ProjectRepository $projectRepository
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return bool
     */
    private function handleFeesForm(FormInterface $feesForm, Request $request, ProjectRepository $projectRepository): bool
    {
        $feesForm->handleRequest($request);

        if ($feesForm->isSubmitted() && $feesForm->isValid()) {
            $project = $feesForm->getData();
            $projectRepository->save($project);

            return true;
        }

        return false;
    }

    /**
     * @param FormInterface     $confidentialityForm
     * @param Request           $request
     * @param ProjectRepository $projectRepository
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return bool
     */
    private function handleConfidentialityForm(FormInterface $confidentialityForm, Request $request, ProjectRepository $projectRepository): bool
    {
        $confidentialityForm->handleRequest($request);

        if ($confidentialityForm->isSubmitted() && $confidentialityForm->isValid()) {
            /** @var Project $project */
            $project = $confidentialityForm->getData();

            if (false === $project->isConfidential()) {
                $project->setConfidentialityDisclaimer(null);
            }

            $projectRepository->save($project);

            return true;
        }

        return false;
    }

    /**
     * @param Project             $project
     * @param FormInterface       $imageForm
     * @param Request             $request
     * @param ProjectRepository   $projectRepository
     * @param ProjectImageManager $projectImageManager
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return bool
     */
    private function handleImageForm(
        Project $project,
        FormInterface $imageForm,
        Request $request,
        ProjectRepository $projectRepository,
        ProjectImageManager $projectImageManager
    ): bool {
        $imageForm->handleRequest($request);

        if ($imageForm->isSubmitted() && $imageForm->isValid()) {
            $imageFile = $imageForm->get('imageFile')->getData();
            $projectImageManager->setImage($project, $imageFile);
            $projectRepository->save($project);

            return true;
        }

        return false;
    }
}
