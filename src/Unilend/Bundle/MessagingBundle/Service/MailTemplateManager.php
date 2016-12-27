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
     * @param EntityManager    $entityManager
     * @param MailQueueManager $mailQueueManager
     * @param                  $defaultLanguage
     */
    public function __construct(EntityManager $entityManager, MailQueueManager $mailQueueManager, $defaultLanguage)
    {
        $this->entityManager    = $entityManager;
        $this->mailQueueManager = $mailQueueManager;
        $this->defaultLanguage  = $defaultLanguage;
    }

    /**
     * @param string $type
     * @param string $sender
     * @param string $senderEmail
     * @param string $subject
     * @param string $content
     */
    public function addTemplate($type, $sender, $senderEmail, $subject, $content)
    {
        /** @var \mail_templates $mailTemplate */
        $oMailTemplate = $this->entityManager->getRepository('mail_templates');

        if (false === $oMailTemplate->exist(\mail_templates::STATUS_ACTIVE, 'type = "' . $type . '" AND status')) {
            $oMailTemplate->type         = $type;
            $oMailTemplate->sender_name  = $sender;
            $oMailTemplate->sender_email = $senderEmail;
            $oMailTemplate->subject      = $subject;
            $oMailTemplate->content      = $content;
            $oMailTemplate->locale       = $this->defaultLanguage;
            $oMailTemplate->status       = \mail_templates::STATUS_ACTIVE;
            $oMailTemplate->create();
        }
    }

    /**
     * @param \mail_templates $mailTemplate
     * @param string          $sender
     * @param string          $senderEmail
     * @param string          $subject
     * @param string          $content
     */
    public function modifyTemplate(\mail_templates &$mailTemplate, $sender, $senderEmail, $subject, $content)
    {
        if ($this->mailQueueManager->existsInMailQueue($mailTemplate->id_mail_template)) {
            $this->archiveTemplate($mailTemplate);
            $this->addTemplate($mailTemplate->type, $sender, $senderEmail, $subject, $content);
        } else {
            $mailTemplate->sender_name  = $sender;
            $mailTemplate->sender_email = $senderEmail;
            $mailTemplate->subject      = $subject;
            $mailTemplate->content      = $content;
            $mailTemplate->update();
        }
    }

    /**
     * @param \mail_templates $mailTemplate
     */
    public function archiveTemplate(\mail_templates $mailTemplate)
    {
        $mailTemplate->status = \mail_templates::STATUS_ARCHIVED;
        $mailTemplate->update();
    }

    /**
     * @return array
     */
    public function getActiveMailTemplates()
    {
        /** @var \mail_templates $mailTemplate */
        $mailTemplate = $this->entityManager->getRepository('mail_templates');
        return $mailTemplate->getActiveMailTemplates();
    }

}
