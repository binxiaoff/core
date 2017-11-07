<?php

namespace Unilend\Bundle\MessagingBundle\Service;

use Doctrine\ORM\EntityManager;
use Pelago\Emogrifier;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates;
use Unilend\Bundle\CoreBusinessBundle\Entity\Translations;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;

class MailTemplateManager
{
    /** @var MailQueueManager */
    private $mailQueueManager;
    /** @var EntityManager */
    private $entityManager;
    /** @var TranslationManager */
    private $translationManager;
    /** @var string */
    private $defaultLanguage;

    /**
     * @param EntityManager      $entityManager
     * @param MailQueueManager   $mailQueueManager
     * @param TranslationManager $translationManager
     * @param string             $defaultLanguage
     */
    public function __construct(
        EntityManager $entityManager,
        MailQueueManager $mailQueueManager,
        TranslationManager $translationManager,
        $defaultLanguage
    )
    {
        $this->entityManager      = $entityManager;
        $this->mailQueueManager   = $mailQueueManager;
        $this->translationManager = $translationManager;
        $this->defaultLanguage    = $defaultLanguage;
    }

    /**
     * @param string             $type
     * @param string|null        $sender
     * @param string|null        $senderEmail
     * @param string|null        $subject
     * @param string|null        $title
     * @param string|null        $content
     * @param MailTemplates|null $header
     * @param MailTemplates|null $footer
     * @param string|null        $recipientType
     * @param string|null        $part
     *
     * @return MailTemplates|null
     */
    public function addTemplate(
        $type,
        $sender = null,
        $senderEmail = null,
        $subject = null,
        $title = null,
        $content = null,
        MailTemplates $header = null,
        MailTemplates $footer = null,
        $recipientType = null,
        $part = MailTemplates::PART_TYPE_CONTENT
    )
    {
        $part         = null === $part ? MailTemplates::PART_TYPE_CONTENT : $part;
        $mailTemplate = $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')->findOneBy([
            'type'   => $type,
            'locale' => $this->defaultLanguage,
            'status' => MailTemplates::STATUS_ACTIVE,
            'part'   => $part
        ]);

        if ($mailTemplate) {
            return null;
        }

        $mailTemplate = new MailTemplates();
        $mailTemplate->setType($type);
        $mailTemplate->setPart($part);
        $mailTemplate->setIdHeader($header);
        $mailTemplate->setIdFooter($footer);
        $mailTemplate->setRecipientType($recipientType);
        $mailTemplate->setSenderName($sender);
        $mailTemplate->setSenderEmail($senderEmail);
        $mailTemplate->setSubject($subject);
        $mailTemplate->setContent($content);
        $mailTemplate->setLocale($this->defaultLanguage);
        $mailTemplate->setStatus(MailTemplates::STATUS_ACTIVE);

        $this->compileTemplate($mailTemplate);

        $this->entityManager->persist($mailTemplate);
        $this->entityManager->flush($mailTemplate);

        if (null !== $title) {
            $this->setTitle($mailTemplate, $title);
        }

        return $mailTemplate;
    }

    /**
     * @param MailTemplates      $mailTemplate
     * @param string|null        $sender
     * @param string|null        $senderEmail
     * @param string|null        $subject
     * @param string|null        $title
     * @param string|null        $content
     * @param MailTemplates|null $header
     * @param MailTemplates|null $footer
     * @param string|null        $recipientType
     */
    public function modifyTemplate(
        MailTemplates $mailTemplate,
        $sender = null,
        $senderEmail = null,
        $subject = null,
        $title = null,
        $content = null,
        MailTemplates $header = null,
        MailTemplates $footer = null,
        $recipientType = null
    )
    {
        if ($this->mailQueueManager->existsInMailQueue($mailTemplate->getIdMailTemplate())) {
            $this->archiveTemplate($mailTemplate);
            $this->addTemplate(
                $mailTemplate->getType(),
                $sender,
                $senderEmail,
                $subject,
                $title,
                $content,
                $header,
                $footer,
                $recipientType,
                $mailTemplate->getPart()
            );
        } else {
            $mailTemplate->setIdHeader($header);
            $mailTemplate->setIdFooter($footer);
            $mailTemplate->setRecipientType($recipientType);
            $mailTemplate->setSenderName($sender);
            $mailTemplate->setSenderEmail($senderEmail);
            $mailTemplate->setSubject($subject);
            $mailTemplate->setContent($content);

            $this->compileTemplate($mailTemplate);

            $this->entityManager->flush($mailTemplate);

            if (null !== $title) {
                $this->setTitle($mailTemplate, $title);
            }
        }

        if (MailTemplates::PART_TYPE_HEADER === $mailTemplate->getPart()) {
            $mailTemplateRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates');
            $templatesWithHeader    = $mailTemplateRepository->findBy(['idHeader' => $mailTemplate]);

            foreach ($templatesWithHeader as $template) {
                $this->modifyTemplate(
                    $template,
                    $template->getSenderName(),
                    $template->getSenderEmail(),
                    $template->getSubject(),
                    null,
                    $template->getContent(),
                    $mailTemplate,
                    $template->getIdFooter(),
                    $template->getRecipientType()
                );
            }
        }

        if (MailTemplates::PART_TYPE_FOOTER === $mailTemplate->getPart()) {
            $mailTemplateRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates');
            $templatesWithFooter    = $mailTemplateRepository->findBy(['idFooter' => $mailTemplate]);

            foreach ($templatesWithFooter as $template) {
                $this->modifyTemplate(
                    $template,
                    $template->getSenderName(),
                    $template->getSenderEmail(),
                    $template->getSubject(),
                    null,
                    $template->getContent(),
                    $template->getIdHeader(),
                    $mailTemplate,
                    $template->getRecipientType()
                );
            }
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
     * @param MailTemplates $mailTemplate
     */
    public function compileTemplate(MailTemplates $mailTemplate)
    {
        if (MailTemplates::PART_TYPE_CONTENT !== $mailTemplate->getPart()) {
            return;
        }

        $content = $mailTemplate->getContent();

        if ($mailTemplate->getIdHeader()) {
            $content = $mailTemplate->getIdHeader()->getContent() . $content;
        }

        if ($mailTemplate->getIdFooter()) {
            $content = $content . $mailTemplate->getIdFooter()->getContent();
        }

        $content = (new Emogrifier($content))->emogrify();
        $content = str_replace(
            ['%5BEMV%20DYN%5D', '%5BEMV%20/DYN%5D'],
            [TemplateMessageProvider::KEYWORDS_PREFIX, TemplateMessageProvider::KEYWORDS_SUFFIX],
            $content
        ); // Emogrifier URL encode content of some attributes (src for instance)
        $mailTemplate->setCompiledContent($content);
    }

    /**
     * @param MailTemplates $mailTemplate
     * @param string        $title
     */
    private function setTitle(MailTemplates $mailTemplate, $title)
    {
        $this->translationManager->deleteTranslation(Translations::SECTION_MAIL_TITLE, $mailTemplate->getType());
        $this->translationManager->addTranslation(Translations::SECTION_MAIL_TITLE, $mailTemplate->getType(), $title);
        $this->translationManager->flush();
    }

    /**
     * @param string|null $recipientType
     * @param string      $part
     *
     * @return MailTemplates[]
     */
    public function getActiveMailTemplates($recipientType = null, $part = MailTemplates::PART_TYPE_CONTENT)
    {
        $criteria = [
            'status' => MailTemplates::STATUS_ACTIVE,
            'part'   => $part
        ];

        if (null !== $recipientType) {
            $criteria['recipientType'] = $recipientType;
        }

        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')->findBy($criteria, ['type' => 'ASC']);
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
            $mailTemplatesUsage[$mailTemplate->getType()] = $mailQueueRepository->getMailTemplateSendFrequency($mailTemplate->getType());
        }

        return $mailTemplatesUsage;
    }
}
