<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates;
use Unilend\Bundle\CoreBusinessBundle\Entity\Translations;

class TemplateMessageProvider
{
    /** @var EntityManager */
    private $entityManager;
    /** @var string */
    private $templateMessageClass;
    /** @var string */
    private $defaultLanguage;
    /** @var TranslatorInterface */
    private $translator;
    /** @var LoggerInterface */
    private $logger;

    /**
     * TemplateMessageProvider constructor.
     *
     * @param EntityManager       $entityManager
     * @param string              $templateMessageClass
     * @param string              $defaultLanguage
     * @param TranslatorInterface $translator
     */
    public function __construct(EntityManager $entityManager, $templateMessageClass, $defaultLanguage, TranslatorInterface $translator)
    {
        $this->entityManager        = $entityManager;
        $this->templateMessageClass = $templateMessageClass;
        $this->defaultLanguage      = $defaultLanguage;
        $this->translator           = $translator;
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
     * @param string     $template
     * @param array|null $variables
     * @param bool       $wrapVariables
     *
     * @return TemplateMessage
     */
    public function newMessage($template, array $variables = null, $wrapVariables = true)
    {
        $mailTemplate = $this->entityManager->getRepository('UnilendCoreBusinessBundle:MailTemplates')->findOneBy([
            'type'   => $template,
            'locale' => $this->defaultLanguage,
            'status' => MailTemplates::STATUS_ACTIVE,
            'part'   => MailTemplates::PART_TYPE_CONTENT
        ]);

        if (null === $mailTemplate) {
            throw new \InvalidArgumentException('The mail template ' . $template . ' for the language ' . $this->defaultLanguage . ' is not found.');
        }

        $body   = $mailTemplate->getContent();
        $header = $mailTemplate->getIdHeader();
        $footer = $mailTemplate->getIdFooter();

        if ($header) {
            $body = $header->getContent() . $body;
            $variables['title'] = $this->translator->trans(Translations::SECTION_MAIL_TITLE . '_' . $mailTemplate->getType());
        }

        if ($footer) {
            $body = $body . $footer->getContent();
        }

        if ($wrapVariables) {
            $variables = $this->wrapVariables($variables);
        }

        $subject  = strtr($mailTemplate->getSubject(), $variables);
        $body     = strtr($body, $variables);
        $fromName = strtr($mailTemplate->getSenderName(), $variables);


        /** @var TemplateMessage $message */
        $message = new $this->templateMessageClass($mailTemplate->getIdMailTemplate());
        $message
            ->setVariables($variables)
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
     * @param array  $variables
     * @param string $prefix
     * @param string $suffix
     *
     * @return array
     */
    private function wrapVariables($variables, $prefix = '[EMV DYN]', $suffix = '[EMV /DYN]')
    {
        $wrappedVars = [];
        foreach ($variables as $key => $value) {
            $wrappedVars[$prefix . $key . $suffix] = $value;
        }

        return $wrappedVars;
    }
}
