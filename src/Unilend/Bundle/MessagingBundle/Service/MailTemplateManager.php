<?php

namespace Unilend\Bundle\MessagingBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class MailTemplateManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var MailQueueManager */
    private $mailQueueManager;
    /** @var  EntityManager */
    private $entityManager;

    /**
     * MailTextManager constructor.
     *
     * @param EntityManager          $entityManager
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param MailQueueManager       $mailQueueManager
     * @param                        $defaultLanguage
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        MailQueueManager $mailQueueManager,
        $defaultLanguage
    ) {
        $this->entityManager          = $entityManager;
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->mailQueueManager       = $mailQueueManager;
        $this->defaultLanguage        = $defaultLanguage;
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
        $mailTemplate = $this->entityManagerSimulator->getRepository('mail_templates');

        if (false === $mailTemplate->exist(MailTemplates::STATUS_ACTIVE, 'type = "' . $type . '" AND status')) {
            $mailTemplate->type         = $type;
            $mailTemplate->sender_name  = $sender;
            $mailTemplate->sender_email = $senderEmail;
            $mailTemplate->subject      = $subject;
            $mailTemplate->content      = $content;
            $mailTemplate->locale       = $this->defaultLanguage;
            $mailTemplate->status       = MailTemplates::STATUS_ACTIVE;
            $mailTemplate->create();
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
        $mailTemplate->status = MailTemplates::STATUS_ARCHIVED;
        $mailTemplate->update();
    }

    /**
     * @param string|null $recipientType
     *
     * @return array
     */
    public function getActiveMailTemplates($recipientType = null)
    {
        if (null === $recipientType) {
            return $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')->findBy(['status' => MailTemplates::STATUS_ACTIVE]);
        }

        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')->findBy(['status' => MailTemplates::STATUS_ACTIVE, 'recipientType' => $recipientType]);
    }
}
