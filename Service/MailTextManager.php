<?php


namespace Unilend\Service;

use Unilend\Service\Simulator\EntityManager;

class MailTextManager
{
    /** @var EntityManager */
    private $oEntityManager;

    /**
     * MailTextManager constructor.
     *
     * @param EntityManager $oEntityManager
     * @param MailQueueManager $oMailQueueManager
     */
    public function __construct(EntityManager $oEntityManager, MailQueueManager $oMailQueueManager)
    {
        $this->oEntityManager    = $oEntityManager;
        $this->oMailQueueManager = $oMailQueueManager;
    }

    public function addMailsText($sType, $sSender, $sSenderEmail, $sSubject, $sContent)
    {
        /** @var \mails_text $oMailText */
        $oMailText            = $this->oEntityManager->getRepository('mails_text');
        $oMailText->type      = $sType;
        $oMailText->exp_name  = $sSender;
        $oMailText->exp_email = $sSenderEmail;
        $oMailText->subject   = $sSubject;
        $oMailText->content   = $sContent;
        $oMailText->lang      = 'fr';
        $oMailText->status    = \mails_text::STATUS_ACTIVE;
        $oMailText->create();

    }

    public function modifyMailsText($iTemplateID, $sType, $sSender, $sSenderEmail, $sSubject, $sContent)
    {
        /** @var \mails_text $oMailText */
        $oMailText            = $this->oEntityManager->getRepository('mails_text');
        $oMailText->get($iTemplateID);
        if ($this->oMailQueueManager->existsInMailQueue($iTemplateID)){
            $this->archiveMailsText($oMailText);
            $this->addMailsText($sType, $sSender, $sSenderEmail, $sSubject, $sContent);
        } else {
            $oMailText->type      = $sType;
            $oMailText->exp_name  = $sSender;
            $oMailText->exp_email = $sSenderEmail;
            $oMailText->subject   = $sSubject;
            $oMailText->content   = $sContent;
            $oMailText->update();
        }
    }

    public function archiveMailsText(\mails_text $oMailText)
    {
        $oMailText->status = \mails_text::STATUS_ARCHIVED;
        $oMailText->type   = $oMailText->type . '_archived';
        $oMailText->update();

    }

    public function getActiveMailsText()
    {
        /** @var \mails_text $oMailText */
        $oMailText  = $this->oEntityManager->getRepository('mails_text');
        $oStatement = $oMailText->getActiveMailsTexts();
        $aTemplates = array();

        while ($aRow = $oStatement->fetch(\PDO::FETCH_ASSOC)) {
            $aTemplates[] = $aRow;
        }

        return $aTemplates;
    }

}