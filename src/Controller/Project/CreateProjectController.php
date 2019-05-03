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
use Unilend\Entity\Attachment;
use Unilend\Entity\Clients;
use Unilend\Entity\Project;
use Unilend\Form\Project\ProjectType;
use Unilend\Service\AttachmentManager;
use Unilend\Service\Project\ProjectCreationManager;

/**
 * @Security("is_granted('ROLE_USER')")
 */
class CreateProjectController extends AbstractController
{
    /**
     * @Route("/projet/depot", name="create_project", methods={"GET", "POST"})
     *
     * @param Request                    $request
     * @param ProjectCreationManager     $projectCreationManager
     * @param UserInterface|Clients|null $client
     * @param AttachmentManager          $attachmentManager
     *
     * @throws Exception
     *
     * @return Response
     */
    public function create(
        Request $request,
        ProjectCreationManager $projectCreationManager,
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

            $projectCreationManager->handleBlamableCreation($project, $client);

            return $this->redirectToRoute('project_creation_success');
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
