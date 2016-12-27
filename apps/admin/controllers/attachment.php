<?php

/**
 * Class attachmentController
 */
class attachmentController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireView   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $this->catchAll = true;

        $this->users->checkAccess();
    }

    public function _download()
    {
        $iAttachmentId  = $this->params[1];
        $sFileName      = $this->params[3];

        if (false === is_numeric($iAttachmentId)) {
            header('Location: ' . $this->lurl);
        }

        /** @var attachment $oAttachment */
        $oAttachment        = $this->loadData('attachment');
        /** @var attachment_type $oAttachmentType */
        $oAttachmentType    = $this->loadData('attachment_type');
        /** @var attachment_helper $oAttachmentHelper */
        $oAttachmentHelper = $this->loadLib('attachment_helper', array($oAttachment, $oAttachmentType, $this->path));

        $oAttachment->get($iAttachmentId);

        if (!$oAttachment->id || urldecode($sFileName) !== $oAttachment->path) {
            header('Location: ' . $this->lurl);
        }

        $sAttachmentPath = $oAttachmentHelper->getFullPath($oAttachment->type_owner, $oAttachment->id_type) . $oAttachment->path;

        if (file_exists($sAttachmentPath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($sAttachmentPath) . '";');
            @readfile($sAttachmentPath);
        } else {
            header('Location: ' . $this->lurl);
        }
    }
}
