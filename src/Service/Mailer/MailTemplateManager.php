<?php

namespace Unilend\Service\Mailer;

use Doctrine\ORM\EntityManagerInterface;
use RobertoTru\ToInlineStyleEmailBundle\Converter\ToInlineStyleEmailConverter;
use Unilend\Entity\{MailQueue, MailTemplates, Translations};
use Unilend\SwiftMailer\TemplateMessageProvider;
use Unilend\Service\Translation\TranslationManager;

class MailTemplateManager
{
    /** @var MailQueueManager */
    private $mailQueueManager;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TranslationManager */
    private $translationManager;
    /** @var ToInlineStyleEmailConverter */
    private $cssToInlineConverter;
    /** @var string */
    private $defaultLocale;

    /**
     * @param EntityManagerInterface      $entityManager
     * @param MailQueueManager            $mailQueueManager
     * @param TranslationManager          $translationManager
     * @param ToInlineStyleEmailConverter $cssToInlineConverter
     * @param string                      $defaultLocale
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        MailQueueManager $mailQueueManager,
        TranslationManager $translationManager,
        ToInlineStyleEmailConverter $cssToInlineConverter,
        string $defaultLocale
    )
    {
        $this->entityManager        = $entityManager;
        $this->mailQueueManager     = $mailQueueManager;
        $this->translationManager   = $translationManager;
        $this->cssToInlineConverter = $cssToInlineConverter;
        $this->defaultLocale        = $defaultLocale;
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
        $mailTemplate = $this->entityManager->getRepository(MailTemplates::class)->findOneBy([
            'type'   => $type,
            'locale' => $this->defaultLocale,
            'status' => MailTemplates::STATUS_ACTIVE,
            'part'   => $part
        ]);

        if ($mailTemplate) {
            return null;
        }

        $mailTemplate = new MailTemplates();
        $mailTemplate
            ->setType($type)
            ->setPart($part)
            ->setIdHeader($header)
            ->setIdFooter($footer)
            ->setRecipientType($recipientType)
            ->setSenderName($sender)
            ->setSenderEmail($senderEmail)
            ->setSubject($subject)
            ->setContent($content)
            ->setLocale($this->defaultLocale)
            ->setStatus(MailTemplates::STATUS_ACTIVE);

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
        $templatesWithHeader = [];
        $templatesWithFooter = [];

        if (MailTemplates::PART_TYPE_HEADER === $mailTemplate->getPart()) {
            $mailTemplateRepository = $this->entityManager->getRepository(MailTemplates::class);
            $templatesWithHeader    = $mailTemplateRepository->findBy(['idHeader' => $mailTemplate]);
        } elseif (MailTemplates::PART_TYPE_FOOTER === $mailTemplate->getPart()) {
            $mailTemplateRepository = $this->entityManager->getRepository(MailTemplates::class);
            $templatesWithFooter    = $mailTemplateRepository->findBy(['idFooter' => $mailTemplate]);
        }

        if ($this->mailQueueManager->existsInMailQueue($mailTemplate->getIdMailTemplate())) {
            $this->archiveTemplate($mailTemplate);
            $mailTemplate = $this->addTemplate(
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
            $mailTemplate
                ->setIdHeader($header)
                ->setIdFooter($footer)
                ->setRecipientType($recipientType)
                ->setSenderName($sender)
                ->setSenderEmail($senderEmail)
                ->setSubject($subject)
                ->setContent($content);

            $this->compileTemplate($mailTemplate);

            $this->entityManager->flush($mailTemplate);

            if (null !== $title) {
                $this->setTitle($mailTemplate, $title);
            }
        }

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

        $content = $this->cssToInlineConverter->inlineCSS($content, null);
        $content = str_replace(
            ['%5BEMV%20DYN%5D', '%5BEMV%20/DYN%5D'],
            [TemplateMessageProvider::KEYWORDS_PREFIX, TemplateMessageProvider::KEYWORDS_SUFFIX],
            $content
        ); // CSS to inline converter urlencode content of some attributes (src for instance)
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

        return $this->entityManager->getRepository(MailTemplates::class)->findBy($criteria, ['type' => 'ASC']);
    }

    /**
     * @return array
     */
    public function getMailTemplateUsage()
    {
        $mailQueueRepository = $this->entityManager->getRepository(MailQueue::class);
        $formattedMailTemplatesUsage  = [];

        $mailTemplateUsage = $mailQueueRepository->getMailTemplateSendFrequency();

        foreach ($mailTemplateUsage as $usage) {
            $formattedMailTemplatesUsage[$usage['id_mail_template']] = $usage;
        }

        return $formattedMailTemplatesUsage;
    }
}
