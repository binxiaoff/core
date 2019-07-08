<?php

namespace Unilend\SwiftMailer;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{MailTemplates, Settings, Translations};

class TemplateMessageProvider
{
    public const KEYWORDS_PREFIX = '[EMV DYN]';
    public const KEYWORDS_SUFFIX = '[EMV /DYN]';

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var string */
    private $templateMessageFQCN;
    /** @var string */
    private $defaultLocale;
    /** @var TranslatorInterface */
    private $translator;
    /** @var string */
    private $staticUrl;
    /** @var string */
    private $frontUrl;
    /** @var string */
    private $adminUrl;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param string                 $templateMessageFQCN
     * @param string                 $defaultLocale
     * @param TranslatorInterface    $translator
     * @param Packages               $assetsPackages
     * @param string                 $frontUrl
     * @param string                 $adminUrl
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        string $templateMessageFQCN,
        string $defaultLocale,
        TranslatorInterface $translator,
        Packages $assetsPackages,
        string $frontUrl,
        string $adminUrl
    ) {
        $this->entityManager       = $entityManager;
        $this->templateMessageFQCN = $templateMessageFQCN;
        $this->defaultLocale       = $defaultLocale;
        $this->translator          = $translator;
        $this->staticUrl           = $assetsPackages->getUrl('');
        $this->frontUrl            = $frontUrl;
        $this->adminUrl            = $adminUrl;
    }

    /**
     * @required
     *
     * @param LoggerInterface|null $logger
     *
     * @return $this
     */
    public function setLogger(?LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param string $templateName
     * @param array  $keywords
     * @param bool   $wrapKeywords
     *
     * @return TemplateMessage
     */
    public function newMessage(string $templateName, array $keywords = [], bool $wrapKeywords = true)
    {
        $mailTemplate = $this->entityManager->getRepository(MailTemplates::class)->findOneBy([
            'type'   => $templateName,
            'locale' => $this->defaultLocale,
            'status' => MailTemplates::STATUS_ACTIVE,
            'part'   => MailTemplates::PART_TYPE_CONTENT,
        ]);

        if (null === $mailTemplate) {
            throw new InvalidArgumentException(sprintf('The mail template %s for the language %s is not found.', $templateName, $this->defaultLocale));
        }

        return $this->setMessageAttributes($mailTemplate, $keywords, $wrapKeywords);
    }

    /**
     * @param MailTemplates $mailTemplate
     * @param array         $keywords
     * @param bool          $wrapKeywords
     *
     * @return TemplateMessage
     */
    public function newMessageByTemplate(MailTemplates $mailTemplate, array $keywords = [], bool $wrapKeywords = true): TemplateMessage
    {
        return $this->setMessageAttributes($mailTemplate, $keywords, $wrapKeywords);
    }

    /**
     * @param MailTemplates $mailTemplate
     * @param array         $keywords
     * @param bool          $wrapKeywords
     *
     * @return TemplateMessage
     */
    private function setMessageAttributes(MailTemplates $mailTemplate, array $keywords = [], bool $wrapKeywords = true): TemplateMessage
    {
        $commonKeywords      = $this->getCommonKeywords();
        $overwrittenKeywords = array_intersect_key($keywords, $commonKeywords);

        if (false === empty($overwrittenKeywords) && $this->logger instanceof LoggerInterface) {
            $this->logger->warning(
                sprintf('Following keywords are overwritten by common keywords in %s email: %s', $mailTemplate->getType(), implode(', ', array_keys($overwrittenKeywords)))
            );
        }

        if ($mailTemplate->getIdHeader()) {
            $keywords['title'] = strtr($this->translator->trans(Translations::SECTION_MAIL_TITLE . '_' . $mailTemplate->getType()), $keywords);

            if (false !== mb_strpos($keywords['title'], self::KEYWORDS_SUFFIX) && false !== mb_strpos($keywords['title'], self::KEYWORDS_PREFIX)) {
                $keywords['title'] = str_replace(self::KEYWORDS_SUFFIX, '', str_replace(self::KEYWORDS_PREFIX, '', $keywords['title']));
            }

            $keywords = array_merge($commonKeywords, $keywords);
        }

        if ($wrapKeywords) {
            $keywords = $this->wrapKeywords($keywords);
        }

        $fromName = strtr($mailTemplate->getSenderName(), $keywords);
        $subject  = strtr($mailTemplate->getSubject(), $keywords);
        $body     = $mailTemplate->getCompiledContent() ? $mailTemplate->getCompiledContent() : $mailTemplate->getContent();
        $body     = strtr($body, $keywords);

        /** @var TemplateMessage $message */
        $message = new $this->templateMessageFQCN($mailTemplate->getIdMailTemplate());
        $message
            ->setVariables($keywords)
            ->setFrom($mailTemplate->getSenderEmail(), $fromName)
            ->setReplyTo($mailTemplate->getSenderEmail(), $fromName)
            ->setSubject($subject)
            ->setBody($body, 'text/html')
        ;

        if ($this->logger instanceof LoggerInterface) {
            $message->setLogger($this->logger);
        }

        return $message;
    }

    /**
     * @return array
     */
    private function getCommonKeywords()
    {
        $settingsRepository = $this->entityManager->getRepository(Settings::class);

        return [
            'staticUrl'       => $this->staticUrl,
            'frontUrl'        => $this->frontUrl,
            'adminUrl'        => $this->adminUrl,
            'facebookLink'    => $settingsRepository->findOneBy(['type' => 'Facebook'])->getValue(),
            'twitterLink'     => $settingsRepository->findOneBy(['type' => 'Twitter'])->getValue(),
            'borrowerFAQLink' => $settingsRepository->findOneBy(['type' => 'URL FAQ emprunteur'])->getValue(),
            'lenderFAQLink'   => $settingsRepository->findOneBy(['type' => 'URL FAQ preteur'])->getValue(),
            'year'            => date('Y'),
        ];
    }

    /**
     * @param array  $keywords
     * @param string $prefix
     * @param string $suffix
     *
     * @return array
     */
    private function wrapKeywords($keywords, $prefix = self::KEYWORDS_PREFIX, $suffix = self::KEYWORDS_SUFFIX)
    {
        $wrappedVars = [];
        foreach ($keywords as $key => $value) {
            $wrappedVars[$prefix . $key . $suffix] = $value;
        }

        return $wrappedVars;
    }
}
