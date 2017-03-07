<?php

/**
 * Class attachmentController
 */
class attachmentController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->hideDecoration();

        $this->autoFireView = false;
        $this->catchAll = true;

        $this->users->checkAccess();
    }

    public function _download()
    {
        $attachmentId = $this->params[1];
        $path         = $this->params[3];

        if (is_numeric($attachmentId)) {
            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment $attachment */
            $attachment = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Attachment')->find($attachmentId);
            if ($attachment && urldecode($path) == $attachment->getPath()) {
                /** @var \Unilend\Bundle\CoreBusinessBundle\Service\AttachmentManager $attachmentManager */
                $attachmentManager = $this->get('unilend.service.attachment_manager');
                $attachmentPath = $attachmentManager->getFullPath($attachment);
                if (file_exists($attachmentPath)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . basename($attachmentPath) . '";');
                    @readfile($attachmentPath);

                    exit;
                }
            }
        }
        header('location: ' . $this->url . '/protected/document_not_found');
    }
}
