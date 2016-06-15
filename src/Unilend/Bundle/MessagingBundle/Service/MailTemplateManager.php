<?php

namespace Unilend\Bundle\MessagingBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class MailTemplateManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var MailQueueManager */
    private $mailQueueManager;

    /**
     * MailTextManager constructor.
     *
     * @param EntityManager $entityManager
     * @param MailQueueManager $mailQueueManager
     * @param $defaultLanguage
     */
    public function __construct(EntityManager $entityManager, MailQueueManager $mailQueueManager, $defaultLanguage)
    {
        $this->entityManager    = $entityManager;
        $this->mailQueueManager = $mailQueueManager;
        $this->defaultLanguage  = $defaultLanguage;
    }

    /**
     * @param string $sType
     * @param string $sSender
     * @param string $sSenderEmail
     * @param string $sSubject
     * @param string $sContent
     */
    public function addTemplate($sType, $sSender, $sSenderEmail, $sSubject, $sContent)
    {
        /** @var \mail_templates $oMailTemplate */
        $oMailTemplate               = $this->entityManager->getRepository('mail_templates');
        $oMailTemplate->type         = $sType;
        $oMailTemplate->sender_name  = $sSender;
        $oMailTemplate->sender_email = $sSenderEmail;
        $oMailTemplate->subject      = $sSubject;
        $oMailTemplate->content      = $sContent;
        $oMailTemplate->lang         = $this->defaultLanguage;
        $oMailTemplate->status       = \mail_templates::STATUS_ACTIVE;
        $oMailTemplate->create();

    }

    /**
     * @param int $iTemplateID
     * @param string $sType
     * @param string $sSender
     * @param string $sSenderEmail
     * @param string $sSubject
     * @param string $sContent
     */
    public function modifyTemplate($iTemplateID, $sType, $sSender, $sSenderEmail, $sSubject, $sContent)
    {
        /** @var \mail_templates $oMailTemplate */
        $oMailTemplate = $this->entityManager->getRepository('mail_templates');
        $oMailTemplate->get($iTemplateID);
        if ($this->mailQueueManager->existsInMailQueue($iTemplateID)){
            $this->archiveTemplate($oMailTemplate);
            $this->addTemplate($sType, $sSender, $sSenderEmail, $sSubject, $sContent);
        } else {
            $oMailTemplate->type         = $sType;
            $oMailTemplate->sender_name  = $sSender;
            $oMailTemplate->sender_email = $sSenderEmail;
            $oMailTemplate->subject      = $sSubject;
            $oMailTemplate->content      = $sContent;
            $oMailTemplate->update();
        }
    }

    /**
     * @param \mail_templates $oMailTemplate
     */
    public function archiveTemplate(\mail_templates $oMailTemplate)
    {
        $oMailTemplate->status = \mail_templates::STATUS_ARCHIVED;
        $oMailTemplate->update();
    }

    /**
     * @return array
     */
    public function getActiveMailTemplates()
    {
        /** @var \mail_templates $oMailTemplate */
        $oMailTemplate  = $this->entityManager->getRepository('mail_templates');
        return $oMailTemplate->getActiveMailTemplates();
    }

}
