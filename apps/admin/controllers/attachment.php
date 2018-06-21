<?php

use Doctrine\ORM\{
    EntityManager, OptimisticLockException
};
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Attachment, UsersHistory, Zones
};
use Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager;

class attachmentController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->hideDecoration();

        $this->autoFireView = false;

        $this->users->checkAccess();
    }

    public function _download()
    {
        if (isset($this->params[1], $this->params[3]) && false !== filter_var($this->params[1], FILTER_VALIDATE_INT)) {
            $attachmentId = $this->params[1];
            $path         = filter_var($this->params[3], FILTER_SANITIZE_STRING);
            /** @var Attachment $attachment */
            $attachment = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Attachment')->find($attachmentId);

            if ($attachment && urldecode($path) === $attachment->getPath()) {
                /** @var AttachmentManager $attachmentManager */
                $attachmentManager = $this->get('unilend.service.attachment_manager');
                $attachmentPath    = $attachmentManager->getFullPath($attachment);

                if (file_exists($attachmentPath)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . basename($attachmentPath) . '";');
                    @readfile($attachmentPath);

                    exit;
                }
            }
        }

        header('Location: ' . $this->url . '/protected/document_not_found');
        exit;
    }

    public function _remove_project()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->hideDecoration();
        $this->autoFireView = false;

        if (
            false === isset($this->params[0])
            || false === filter_var($this->params[0], FILTER_VALIDATE_INT)
        ) {
            $this->sendAjaxResponse(false, null, ['Document inconnu']);
        }

        /** @var EntityManager $entityManager */
        $entityManager     = $this->get('doctrine.orm.entity_manager');
        $projectAttachment = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachment')->find($this->params[0]);

        if (null === $projectAttachment) {
            $this->sendAjaxResponse(false, null, ['Unable to load project attachment']);
        }

        try {
            $entityManager->remove($projectAttachment);
            $entityManager->flush($projectAttachment);

            $this->sendAjaxResponse(true);
        } catch (OptimisticLockException $exception) {
            /** @var LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->error('Unable to delete project attachment ' . $this->params[0] . ' - Message: ' . $exception->getMessage(), [
                'class'    => __CLASS__,
                'function' => __FUNCTION__,
                'file'     => $exception->getFile(),
                'line'     => $exception->getLine()
            ]);

            $this->sendAjaxResponse(false, null, [$exception->getMessage()]);
        }
    }

    public function _upload_project()
    {
        $this->users->checkAccess(Zones::ZONE_LABEL_BORROWERS);
        $this->hideDecoration();
        $this->autoFireView = false;

        $projectId        = $this->request->request->getInt('id_project');
        $attachmentTypeId = $this->request->request->getInt('id_attachment');

        if (empty($projectId) || empty($attachmentTypeId)) {
            $this->sendAjaxResponse(false, null, ['Paramètres incorrects']);
        }

        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $project       = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);

        if (null === $project) {
            $this->sendAjaxResponse(false, null, ['Projet inconnu']);
        }

        $attachmentType = $entityManager->getRepository('UnilendCoreBusinessBundle:AttachmentType')->find($attachmentTypeId);

        if (null === $attachmentType) {
            $this->sendAjaxResponse(false, null, ['Type de document inconnu']);
        }

        $this->users_history->histo(
            UsersHistory::FORM_ID_PROJECT_UPLOAD,
            'dossier edit etapes 5',
            $_SESSION['user']['id_user'],
            serialize(['id_project' => $project->getIdProject(), 'files' => $_FILES])
        );

        $files = $this->request->files->all();

        if (false === isset($files['file']) || false === is_array($files['file'])) {
            $this->sendAjaxResponse(false, null, ['Mauvais formatage des paramètres']);
        }

        $projectAttachmentType = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachmentType')->findOneBy(['idType' => $attachmentType]);
        $projectAttachments    = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectAttachment')->getAttachedAttachments($project, $attachmentType);

        if (count($projectAttachments) >= $projectAttachmentType->getMaxItems()) {
            $this->sendAjaxResponse(false, null, ['Vous ne pouvez pas charger de document supplémentaire de ce type. Veuillez d‘abord supprimer un des documents existants.']);
        }

        /** @var AttachmentManager $attachmentManager */
        $attachmentManager = $this->get('unilend.service.attachment_manager');
        $response          = [];

        foreach ($files['file'] as $uploadedFile) {
            if ($uploadedFile) {
                try {
                    $attachment        = $attachmentManager->upload($project->getIdCompany()->getIdClientOwner(), $attachmentType, $uploadedFile, false);
                    $projectAttachment = $attachmentManager->attachToProject($attachment, $project);

                    $response[] = [
                        'name'                => $attachment->getOriginalName(),
                        'attachmentId'        => $attachment->getId(),
                        'projectAttachmentId' => $projectAttachment->getId()
                    ];
                } catch (\Doctrine\ORM\OptimisticLockException $exception) {
                    /** @var LoggerInterface $logger */
                    $logger = $this->get('logger');
                    $logger->error('Unable to upload file of type "' . $attachmentType->getLabel() . '" for project ID ' . $project->getIdProject() . ' - Message: ' . $exception->getMessage(), [
                        'id_project' => $project->getIdProject(),
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__,
                        'file'       => $exception->getFile(),
                        'line'       => $exception->getLine(),
                    ]);
                }
            }
        }

        $this->sendAjaxResponse(true, $response);
    }
}
