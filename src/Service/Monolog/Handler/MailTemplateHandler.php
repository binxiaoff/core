<?php

declare(strict_types=1);

namespace Unilend\Service\Monolog\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Swift_Mailer;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Unilend\Service\Mailer\MailTemplateManager;
use Unilend\SwiftMailer\TemplateMessageProvider;

class MailTemplateHandler extends AbstractProcessingHandler
{
    /** @var MailTemplateManager */
    private $mailer;
    /** @var TemplateMessageProvider */
    private $templateMessageProvider;
    /** @var mixed */
    private $securityRecipients;

    /**
     * @param TemplateMessageProvider $templateMessageProvider
     * @param Swift_Mailer            $mailer
     * @param mixed                   $securityRecipients
     * @param int                     $level
     * @param bool                    $bubble
     */
    public function __construct(
        TemplateMessageProvider $templateMessageProvider,
        Swift_Mailer $mailer,
        $securityRecipients,
        $level = Logger::DEBUG,
        $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->templateMessageProvider = $templateMessageProvider;
        $this->mailer                  = $mailer;
        $this->securityRecipients      = $securityRecipients;
    }

    /**
     * Writes the record down to the log of the implementing handler.
     *
     * @param array $record
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function write(array $record)
    {
        $message = $this->templateMessageProvider->newMessage('log', $record);
        $message->setTo($this->securityRecipients);

        $this->mailer->send($message);
    }
}
