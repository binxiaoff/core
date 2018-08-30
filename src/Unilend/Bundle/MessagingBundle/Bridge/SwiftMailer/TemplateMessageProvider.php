<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{MailTemplates, Translations};

class TemplateMessageProvider
{
    const KEYWORDS_PREFIX = '[EMV DYN]';
    const KEYWORDS_SUFFIX = '[EMV /DYN]';

    /** @var EntityManager */
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
     * @param EntityManager       $entityManager
     * @param string              $templateMessageFQCN
     * @param string              $defaultLocale
     * @param TranslatorInterface $translator
     * @param Packages            $assetsPackages
     * @param string              $frontUrl
     * @param string              $adminUrl
     */
    public function __construct(
        EntityManager $entityManager,
        string $templateMessageFQCN,
        string $defaultLocale,
        TranslatorInterface $translator,
        Packages $assetsPackages,
        string $frontUrl,
        string $adminUrl
    )
    {
        $this->entityManager       = $entityManager;
        $this->templateMessageFQCN = $templateMessageFQCN;
        $this->defaultLocale       = $defaultLocale;
        $this->translator          = $translator;
        $this->staticUrl           = $assetsPackages->getUrl('');
        $this->frontUrl            = $frontUrl;
        $this->adminUrl            = $adminUrl;
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
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
    public function newMessage($templateName, array $keywords = [], bool $wrapKeywords = true)
    {
        $mailTemplate = $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')->findOneBy([
            'type'   => $templateName,
            'locale' => $this->defaultLocale,
            'status' => MailTemplates::STATUS_ACTIVE,
            'part'   => MailTemplates::PART_TYPE_CONTENT
        ]);

        if (null === $mailTemplate) {
            throw new \InvalidArgumentException('The mail template ' . $templateName . ' for the language ' . $this->defaultLocale . ' is not found.');
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
     * @throws \Swift_RfcComplianceException
     */
    private function setMessageAttributes(MailTemplates $mailTemplate, array $keywords = [], bool $wrapKeywords = true): TemplateMessage
    {
        $commonKeywords      = $this->getCommonKeywords();
        $overwrittenKeywords = array_intersect_key($keywords, $commonKeywords);

        if (false === empty($overwrittenKeywords) && $this->logger instanceof LoggerInterface) {
            $this->logger->warning('Following keywords are overwritten by common keywords in "' . $mailTemplate->getType() . '" email: ' . implode(', ', array_keys($overwrittenKeywords)));
        }

        if ($mailTemplate->getIdHeader()) {
            $keywords['title'] = strtr($this->translator->trans(Translations::SECTION_MAIL_TITLE . '_' . $mailTemplate->getType()), $keywords);

            if (false !== strpos($keywords['title'], self::KEYWORDS_SUFFIX) && false !== strpos($keywords['title'], self::KEYWORDS_PREFIX)) {
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
            ->setBody($body, 'text/html');

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
        $settingsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings');

        return [
            'staticUrl'       => $this->staticUrl,
            'frontUrl'        => $this->frontUrl,
            'adminUrl'        => $this->adminUrl,
            'facebookLink'    => $settingsRepository->findOneBy(['type' => 'Facebook'])->getValue(),
            'twitterLink'     => $settingsRepository->findOneBy(['type' => 'Twitter'])->getValue(),
            'borrowerFAQLink' => $settingsRepository->findOneBy(['type' => 'URL FAQ emprunteur'])->getValue(),
            'lenderFAQLink'   => $settingsRepository->findOneBy(['type' => 'URL FAQ preteur'])->getValue(),
            'year'            => date('Y')
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
