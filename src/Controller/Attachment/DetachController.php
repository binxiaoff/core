<?php

namespace Unilend\Controller\Attachment;

use Doctrine\ORM\{ORMException, OptimisticLockException};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Unilend\Entity\ProjectAttachment;
use Unilend\Security\Voter\ProjectVoter;
use Unilend\Service\Attachment\ProjectAttachmentManager;

class DetachController extends AbstractController
{
    /**
     * @Route(
     *     "/project/document/{id}/detach",
     *     name="document_detach",
     *     requirements={"id": "\d+"},
     *     options={"expose": true},
     *     methods={"PATCH"},
     *     condition="request.isXmlHttpRequest()"
     * )
     *
     * @param ProjectAttachment        $projectAttachment
     * @param ProjectAttachmentManager $projectAttachmentManager
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return JsonResponse
     */
    public function detach(ProjectAttachment $projectAttachment, ProjectAttachmentManager $projectAttachmentManager): JsonResponse
    {
        $this->denyAccessUnlessGranted(ProjectVoter::ATTRIBUTE_EDIT, $projectAttachment->getProject());
        $projectAttachmentManager->detachFromProject($projectAttachment);

        return $this->json('OK');
    }
}
