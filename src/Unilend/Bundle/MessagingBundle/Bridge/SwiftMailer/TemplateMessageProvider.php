<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates;

class TemplateMessageProvider
{
    /** @var EntityManager */
    private $entityManager;
    /** @var string */
    private $templateMessageClass;
    /** @var string */
    private $defaultLanguage;
    /** @var LoggerInterface */
    private $logger;

    /**
     * TemplateMessageProvider constructor.
     *
     * @param EntityManager $entityManager
     * @param string        $templateMessageClass
     * @param string        $defaultLanguage
     */
    public function __construct(EntityManager $entityManager, $templateMessageClass, $defaultLanguage)
    {
        $this->entityManager        = $entityManager;
        $this->templateMessageClass = $templateMessageClass;
        $this->defaultLanguage      = $defaultLanguage;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @param string $template
     * @param array  $variables
     * @param bool   $wrapVariables
     *
     * @return TemplateMessage
     */
    public function newMessage($template, $variables = null, $wrapVariables = true)
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

        if ($wrapVariables) {
            $variables = $this->wrapVariables($variables);
        }

        $subject  = strtr($mailTemplate->getSubject(), $variables);
        $body     = strtr($mailTemplate->getContent(), $variables);
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
     * @return mixed
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
