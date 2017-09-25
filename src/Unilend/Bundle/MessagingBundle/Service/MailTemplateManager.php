<?php

namespace Unilend\Bundle\MessagingBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates;

class MailTemplateManager
{
    /** @var MailQueueManager */
    private $mailQueueManager;
    /** @var EntityManager */
    private $entityManager;
    /** @var string */
    private $defaultLanguage;

    /**
     * @param EntityManager    $entityManager
     * @param MailQueueManager $mailQueueManager
     * @param string           $defaultLanguage
     */
    public function __construct(
        EntityManager $entityManager,
        MailQueueManager $mailQueueManager,
        $defaultLanguage
    )
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
        $mailTemplate = $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')->findOneBy([
            'type'   => $type,
            'locale' => $this->defaultLanguage,
            'status' => MailTemplates::STATUS_ACTIVE
        ]);

        if (null === $mailTemplate) {
            $mailTemplate = new MailTemplates();
            $mailTemplate->setType($type);
            $mailTemplate->setSenderName($sender);
            $mailTemplate->setSenderEmail($senderEmail);
            $mailTemplate->setSubject($subject);
            $mailTemplate->setContent($content);
            $mailTemplate->setLocale($this->defaultLanguage);
            $mailTemplate->setStatus(MailTemplates::STATUS_ACTIVE);

            $this->entityManager->persist($mailTemplate);
            $this->entityManager->flush($mailTemplate);
        }
    }

    /**
     * @param MailTemplates $mailTemplate
     * @param string        $sender
     * @param string        $senderEmail
     * @param string        $subject
     * @param string        $content
     */
    public function modifyTemplate(MailTemplates $mailTemplate, $sender, $senderEmail, $subject, $content)
    {
        if ($this->mailQueueManager->existsInMailQueue($mailTemplate->getIdMailTemplate())) {
            $this->archiveTemplate($mailTemplate);
            $this->addTemplate($mailTemplate->getType(), $sender, $senderEmail, $subject, $content);
        } else {
            $mailTemplate->setSenderName($sender);
            $mailTemplate->setSenderEmail($senderEmail);
            $mailTemplate->setSubject($subject);
            $mailTemplate->setContent($content);

            $this->entityManager->flush($mailTemplate);
        }
    }

    /**
     * @param MailTemplates $mailTemplate
     */
    public function archiveTemplate(MailTemplates $mailTemplate)
    {
        $mailTemplate->setStatus(MailTemplates::STATUS_ARCHIVED);
        $this->entityManager->flush($mailTemplate);
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

    /**
     * @param array $mailTemplates
     *
     * @return array
     */
    public function getMailTemplateUsage(array $mailTemplates)
    {
        $mailQueueRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailQueue');
        $mailTemplatesUsage  = [];

        foreach ($mailTemplates as $mailTemplate) {
            $mailTemplatesUsage[$mailTemplate->getType()] = $mailQueueRepository->getSendFrequencyForMailTemplate($mailTemplate->getType());
        }

        return $mailTemplatesUsage;
    }
}
