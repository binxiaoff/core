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
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\{File\UploadedFile, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{AcceptedBids, Attachment, Bids, Clients, Loans, Project, ProjectStatusHistory, UnderlyingContract};
use Unilend\Form\Project\ProjectAttachmentCollectionType;
use Unilend\Form\Tranche\TrancheTypeCollectionType;
use Unilend\Repository\{AcceptedBidsRepository, BidsRepository, CompaniesRepository, ProjectAttachmentRepository, ProjectAttachmentTypeRepository, ProjectRepository,
    UnderlyingContractRepository};
use Unilend\Service\{AttachmentManager, DemoMailerManager, ProjectStatusManager};

class EditProjectController extends AbstractController
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
     * @param AttachmentManager               $attachmentManager
     *
     * @throws Exception
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
        AttachmentManager $attachmentManager
    ): Response {
        $regionalBanks = $companyRepository->findRegionalBanks(['name' => 'ASC']);

        $documentForm = $this->get('form.factory')->createNamedBuilder('attachments')
            ->add('projectAttachments', ProjectAttachmentCollectionType::class)
            ->getForm()
        ;
        $trancheForm = $this->get('form.factory')->createNamedBuilder('tranches', FormType::class, $project, ['data_class' => Project::class])
            ->add('tranches', TrancheTypeCollectionType::class)
            ->getForm()
        ;

        $documentForm->handleRequest($request);
        $trancheForm->handleRequest($request);

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

        if ($trancheForm->isSubmitted() && $trancheForm->isValid()) {
            $project = $trancheForm->getData();
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
            'trancheForm'          => $trancheForm->createView(),
        ];

        return $this->render('demo/project_request_details.html.twig', $template);
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
     * @Route("/projet/financement/{hash}", name="edit_project_status_publish", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/finance/{hash}", name="edit_project_status_funded", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/signature/{hash}", name="edit_project_status_contracts_redacted", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/remboursement/{hash}", name="edit_project_status_signed", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/rembourse/{hash}", name="edit_project_status_finished", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     * @Route("/projet/perte/{hash}", name="edit_project_status_lost", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}"})
     *
     * @IsGranted("edit", subject="project")
     *
     * @param Project                      $project
     * @param Request                      $request
     * @param UserInterface|Clients|null   $user
     * @param ProjectStatusManager         $projectStatusManager
     * @param DemoMailerManager            $mailerManager
     * @param LoggerInterface              $logger
     * @param BidsRepository               $bidsRepository
     * @param UnderlyingContractRepository $underlyingContractRepository
     * @param AcceptedBidsRepository       $acceptedBidRepository
     * @param EntityManagerInterface       $entityManager
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
        DemoMailerManager $mailerManager,
        LoggerInterface $logger,
        BidsRepository $bidsRepository,
        UnderlyingContractRepository $underlyingContractRepository,
        AcceptedBidsRepository $acceptedBidRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $status = null;
        $route  = $request->get('_route');

        switch ($route) {
            case 'edit_project_status_abandon':
                $status = ProjectStatusHistory::STATUS_CANCELLED;

                break;
            case 'edit_project_status_publish':
                $status = ProjectStatusHistory::STATUS_PUBLISHED;

                break;
            case 'edit_project_status_funded':
                $status = ProjectStatusHistory::STATUS_FUNDED;

                break;
            case 'edit_project_status_contracts_redacted':
                $status = ProjectStatusHistory::STATUS_CONTRACTS_REDACTED;

                break;
            case 'edit_project_status_signed':
                $status = ProjectStatusHistory::STATUS_CONTRACTS_SIGNED;

                break;
            case 'edit_project_status_finished':
                $status = ProjectStatusHistory::STATUS_FINISHED;

                break;
            case 'edit_project_status_lost':
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
                        $logger->error('An error occurred while sending project publication email. Message: ' . $exception->getMessage(), [
                            'class'    => __CLASS__,
                            'function' => __FUNCTION__,
                            'file'     => $exception->getFile(),
                            'line'     => $exception->getLine(),
                        ]);
                    }

                    break;
                case ProjectStatusHistory::STATUS_FUNDED:
                    $this->closeProject($project, $bidsRepository, $underlyingContractRepository, $acceptedBidRepository, $logger, $entityManager);

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
     * @param Project             $project
     * @param Request             $request
     * @param DemoMailerManager   $mailerManager
     * @param CompaniesRepository $companyRepository
     * @param ProjectRepository   $projectRepository
     * @param LoggerInterface     $logger
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return Response
     */
    public function update(
        Project $project,
        Request $request,
        DemoMailerManager $mailerManager,
        CompaniesRepository $companyRepository,
        ProjectRepository $projectRepository,
        LoggerInterface $logger
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
                    $logger->error('An error occurred while sending scoring update email. Message: ' . $exception->getMessage(), [
                        'class'    => __CLASS__,
                        'function' => __FUNCTION__,
                        'file'     => $exception->getFile(),
                        'line'     => $exception->getLine(),
                    ]);
                }

                $outputValue = $project->getInternalRatingScore();

                break;
        }

        $projectRepository->save($project);

        return $this->json([
            'success'  => true,
            'newValue' => $outputValue,
        ]);
    }

    /**
     * @param Project                      $project
     * @param BidsRepository               $bidsRepository
     * @param UnderlyingContractRepository $underlyingContractRepository
     * @param AcceptedBidsRepository       $acceptedBidRepository
     * @param LoggerInterface              $logger
     * @param EntityManagerInterface       $entityManager
     *
     * @throws ConnectionException
     */
    private function closeProject(
        Project $project,
        BidsRepository $bidsRepository,
        UnderlyingContractRepository $underlyingContractRepository,
        AcceptedBidsRepository $acceptedBidRepository,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ): void {
        $bids = $bidsRepository->findBy([
            'project' => $project,
            'status'  => [Bids::STATUS_ACCEPTED, Bids::STATUS_PENDING],
        ]);

        $contract = $underlyingContractRepository->findOneBy(['label' => UnderlyingContract::CONTRACT_BDC]);

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
                    ->setWallet($bid->getWallet())
                    ->setTranche($bid->getTranche())
                    ->setUnderlyingContract($contract)
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
}
