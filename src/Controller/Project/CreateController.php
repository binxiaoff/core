<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use League\Flysystem\{FileExistsException, FileNotFoundException};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\{File\UploadedFile, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Attachment, Clients, Project, ProjectStatus};
use Unilend\Form\Project\ProjectType;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\{Attachment\AttachmentManager, Project\ProjectImageManager, User\RealUserFinder};

class CreateController extends AbstractController
{
    /**
     * @Route("/projet/depot", name="project_creation_operation_choice", methods={"GET"})
     *
     * @return Response
     */
    public function choice(): Response
    {
        return $this->render('project/create/choice.html.twig');
    }

    /**
     * @Route("/projet/depot/{operationType}", name="project_creation", methods={"GET", "POST"}, requirements={"operationType": "1|2"})
     *
     * @param Request                    $request
     * @param string                     $operationType
     * @param ProjectRepository          $projectRepository
     * @param UserInterface|Clients|null $client
     * @param AttachmentManager          $attachmentManager
     * @param ProjectImageManager        $imageManager
     * @param RealUserFinder             $realUserFinder
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws FileExistsException
     * @throws FileNotFoundException
     *
     * @return Response
     */
    public function create(
        Request $request,
        string $operationType,
        ProjectRepository $projectRepository,
        ?UserInterface $client,
        AttachmentManager $attachmentManager,
        ProjectImageManager $imageManager,
        RealUserFinder $realUserFinder
    ): Response {
        $project = (new Project($realUserFinder))->setOperationType((int) $operationType);

        $form = $this->createForm(ProjectType::class, $project, ['operation_type' => $operationType]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Project $project */
            $project                = $form->getData();
            $projectAttachmentForms = $form->get('projectAttachments');
            /** @var FormInterface $projectAttachmentForm */
            foreach ($projectAttachmentForms as $projectAttachmentForm) {
                $attachmentForm = $projectAttachmentForm->get('attachment');
                /** @var Attachment $attachment */
                $attachment = $attachmentForm->getData();
                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $attachmentForm->get('file')->getData();
                $companyOwner = $project->getBorrowerCompany();
                $attachmentManager->upload(null, $companyOwner, $client, null, $attachment, $uploadedFile);
            }

            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $imageManager->setImage($project, $imageFile);
            }

            $project
                ->setSubmitterClient($client)
                ->setSubmitterCompany($client->getCompany())
                ->setCurrentStatus(ProjectStatus::STATUS_REQUESTED, $realUserFinder)
            ;

            if ($arranger = $form->get('arranger')->getData()) {
                $project->setArranger($arranger, $realUserFinder);
            }

            if ($arranger = $form->get('deputyArranger')->getData()) {
                $project->setDeputyArranger($arranger, $realUserFinder);
            }

            if ($arranger = $form->get('run')->getData()) {
                $project->setRun($arranger, $realUserFinder);
            }

            if ($arranger = $form->get('loanOfficer')->getData()) {
                $project->setLoanOfficer($arranger, $realUserFinder);
            }

            if ($arranger = $form->get('securityTrustee')->getData()) {
                $project->setSecurityTrustee($arranger, $realUserFinder);
            }

            $projectRepository->save($project);

            return $this->redirectToRoute('edit_project_details', ['hash' => $project->getHash()]);
        }

        return $this->render('project/create/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
