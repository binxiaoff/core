<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\{File\UploadedFile, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Attachment, Clients, Project, ProjectStatusHistory};
use Unilend\Form\Project\ProjectType;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\AttachmentManager;

class CreateController extends AbstractController
{
    /**
     * @Route("/projet/depot", name="project_creation", methods={"GET", "POST"})
     *
     * @param Request                    $request
     * @param ProjectRepository          $projectRepository
     * @param UserInterface|Clients|null $client
     * @param AttachmentManager          $attachmentManager
     *
     * @throws Exception
     *
     * @return Response
     */
    public function create(
        Request $request,
        ProjectRepository $projectRepository,
        ?UserInterface $client,
        AttachmentManager $attachmentManager
    ) {
        $form = $this->createForm(ProjectType::class);

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

            $projectStatusHistory = (new ProjectStatusHistory())
                ->setStatus(ProjectStatusHistory::STATUS_REQUESTED)
                ->setAddedBy($client)
            ;

            $project
                ->setSubmitterClient($client)
                ->setSubmitterCompany($client->getCompany())
                ->setProjectStatusHistory($projectStatusHistory)
            ;

            $projectRepository->save($project);

            return $this->redirectToRoute('edit_project_details', ['hash' => $project->getHash()]);
        }

        return $this->render('project/create/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
