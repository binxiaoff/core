<?php

namespace Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer;

use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\MailTemplates;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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
        /** @var \mail_templates $mailTemplate */
        $mailTemplate = $this->entityManager->getRepository('mail_templates');
        if (false === $mailTemplate->get($template, 'status = ' . MailTemplates::STATUS_ACTIVE . ' AND locale = "' . $this->defaultLanguage . '" AND type')) {
            throw new \InvalidArgumentException('The mail template ' . $template . ' for the language ' . $this->defaultLanguage . ' is not found.');
        }

        if ($wrapVariables) {
            $variables = $this->wrapVariables($variables);
        }

        $subject  = strtr($mailTemplate->subject, $variables);
        $body     = strtr($mailTemplate->content, $variables);
        $fromName = strtr($mailTemplate->sender_name, $variables);

        /** @var TemplateMessage $message */
        $message = new $this->templateMessageClass($mailTemplate->id_mail_template);
        $message->setVariables($variables)
                ->setFrom($mailTemplate->sender_email, $fromName)
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
