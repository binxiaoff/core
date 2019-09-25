<?php

declare(strict_types=1);

namespace Unilend\Service\Mailer;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Unilend\Entity\{AbstractMailPart, MailFooter, MailHeader, MailLayout, MailQueue, MailTemplate, Translations};
use Unilend\Service\Translation\TranslationManager;

class MailTemplateManager
{
    /** @var MailQueueManager */
    private $mailQueueManager;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var TranslationManager */
    private $translationManager;
    /** @var string */
    private $defaultLocale;

    /**
     * @param EntityManagerInterface $entityManager
     * @param MailQueueManager       $mailQueueManager
     * @param TranslationManager     $translationManager
     * @param string                 $defaultLocale
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        MailQueueManager $mailQueueManager,
        TranslationManager $translationManager,
        string $defaultLocale
    ) {
        $this->entityManager      = $entityManager;
        $this->mailQueueManager   = $mailQueueManager;
        $this->translationManager = $translationManager;
        $this->defaultLocale      = $defaultLocale;
    }

    /**
     * @param string            $type
     * @param string|null       $sender
     * @param string|null       $senderEmail
     * @param string|null       $subject
     * @param string|null       $title
     * @param string|null       $content
     * @param MailTemplate|null $header
     * @param MailFooter|null   $footer
     *
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @return MailTemplate|null
     */
    public function addTemplate(
        $type,
        $sender = null,
        $senderEmail = null,
        $subject = null,
        $title = null,
        $content = null,
        MailTemplate $header = null,
        MailFooter $footer = null
    ): ?MailTemplate {
        $mailTemplate = $this->entityManager->getRepository(MailTemplate::class)->findOneBy([
            'type'   => $type,
            'locale' => $this->defaultLocale,
        ]);

        if ($mailTemplate) {
            return null;
        }

        $mailLayout = $this->entityManager->getRepository(MailLayout::class)->findBy([], [], 1);
        $mailLayout = $mailLayout[0];

        $mailTemplate = new MailTemplate($type, $mailLayout);
        $mailTemplate
            ->setHeader($header)
            ->setFooter($footer)
            ->setSenderName($sender)
            ->setSenderEmail($senderEmail)
            ->setSubject($subject)
            ->setContent($content)
            ->setLocale($this->defaultLocale)
        ;

        $this->entityManager->persist($mailTemplate);
        $this->entityManager->flush($mailTemplate);

        if (null !== $title) {
            $this->setTitle($mailTemplate, $title);
        }

        return $mailTemplate;
    }

    /**
     * @param MailTemplate $mailTemplate
     * @param string|null  $sender
     * @param string|null  $senderEmail
     * @param string|null  $subject
     * @param string|null  $title
     * @param string|null  $content
     * @param MailHeader   $header
     * @param MailFooter   $footer
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NonUniqueResultException
     */
    public function modifyTemplate(
        MailTemplate $mailTemplate,
        $sender = null,
        $senderEmail = null,
        $subject = null,
        $title = null,
        $content = null,
        MailHeader $header = null,
        MailFooter $footer = null
    ): void {
        if ($this->mailQueueManager->existsInMailQueue($mailTemplate->getId())) {
            $this->archive($mailTemplate);
            $this->addTemplate(
                $mailTemplate->getType(),
                $sender,
                $senderEmail,
                $subject,
                $title,
                $content,
                $header,
                $footer
            );
        } else {
            $mailTemplate
                ->setHeader($header)
                ->setFooter($footer)
                ->setSenderName($sender)
                ->setSenderEmail($senderEmail)
                ->setSubject($subject)
                ->setContent($content)
            ;

            $this->entityManager->flush($mailTemplate);

            if (null !== $title) {
                $this->setTitle($mailTemplate, $title);
            }
        }
    }

    /**
     * @param AbstractMailPart $mailPart
     */
    public function archive(AbstractMailPart $mailPart): void
    {
        $this->entityManager->remove($mailPart);
        $this->entityManager->flush($mailPart);
    }

    /**
     * @throws DBALException
     *
     * @return array
     */
    public function getMailTemplateUsage(): array
    {
        $mailQueueRepository         = $this->entityManager->getRepository(MailQueue::class);
        $formattedMailTemplatesUsage = [];

        $mailTemplateUsage = $mailQueueRepository->getMailTemplateSendFrequency();

        foreach ($mailTemplateUsage as $usage) {
            $formattedMailTemplatesUsage[$usage['id_mail_template']] = $usage;
        }

        return $formattedMailTemplatesUsage;
    }

    /**
     * @param MailTemplate $mailTemplate
     * @param string       $title
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function setTitle(MailTemplate $mailTemplate, $title): void
    {
        $this->translationManager->deleteTranslation(Translations::SECTION_MAIL_TITLE, $mailTemplate->getType());
        $this->translationManager->addTranslation(Translations::SECTION_MAIL_TITLE, $mailTemplate->getType(), $title);
        $this->translationManager->flush();
    }
}
