<?php

/**
 * Class attachmentController
 */
class attachmentController extends bootstrap
{
    /**
     * @param $command
     * @param $config
     * @param $app
     */
    public function attachmentController($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

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
            header('location:' . $this->lurl);
        }

        /** @var attachment $oAttachment */
        $oAttachment        = $this->loadData('attachment');
        /** @var attachment_type $oAttachmentType */
        $oAttachmentType    = $this->loadData('attachment_type');
        /** @var attachment_helper $oAttachmentHelper */
        $oAttachmentHelper = $this->loadLib('attachment_helper', array($oAttachment, $oAttachmentType));

        $oAttachment->get($iAttachmentId);

        if (!$oAttachment->id || urldecode($sFileName) !== $oAttachment->path) {
            header('location:' . $this->lurl);
        }

        $sAttachmentPath = $oAttachmentHelper->getUploadPath($oAttachment->type_owner, $oAttachment->id_type);

        if (file_exists($this->path . $sAttachmentPath)) {
            $url = ($this->path . $sAttachmentPath);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($url) . '";');
            @readfile($url);
        } else {
            header('location:' . $this->lurl);
        }
    }
}