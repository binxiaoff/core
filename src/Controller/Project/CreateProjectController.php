<?php

declare(strict_types=1);

namespace Unilend\Controller\Project;

use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\{File\UploadedFile, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Attachment, Clients, Project, ProjectStatusHistory, ProjectsStatus};
use Unilend\Form\Project\ProjectType;
use Unilend\Repository\ProjectRepository;
use Unilend\Service\AttachmentManager;

/**
 * @Security("is_granted('ROLE_USER')")
 */
class CreateProjectController extends AbstractController
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
                ->setStatus(ProjectsStatus::STATUS_REQUESTED)
                ->setAddedBy($client)
            ;

            $project
                ->setSubmitterClient($client)
                ->setSubmitterCompany($client->getCompany())
                ->setProjectStatusHistory($projectStatusHistory)
            ;

            $projectRepository->save($project);

            return $this->redirectToRoute('demo_project_details', ['hash' => $project->getHash()]);
        }

        return $this->render('project/create_project/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/projet/depot/succes", name="project_creation_success", methods={"GET"})
     */
    public function createSuccess()
    {
    }
}
