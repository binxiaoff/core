<?php

declare(strict_types=1);

namespace KLS\Core\Service\Monolog\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Swift_Mailer;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class MailHandler extends AbstractProcessingHandler
{
    private Swift_Mailer $mailer;
    /** @var mixed */
    private $securityRecipients;

    private Environment $twig;

    private string $senderAddress;

    /**
     * @param mixed  $securityRecipients
     * @param int    $level
     * @param bool   $bubble
     * @param string $senderAddress
     */
    public function __construct(
        Swift_Mailer $mailer,
        Environment $twig,
        $securityRecipients,
        $level = Logger::CRITICAL, // @see https://github.com/symfony/monolog-bundle/issues/322
        $bubble = true,
        $senderAddress = 'support@kls-platform.com'
    ) {
        parent::__construct($level, $bubble);
        $this->mailer             = $mailer;
        $this->securityRecipients = $securityRecipients;
        $this->twig               = $twig;
        $this->senderAddress      = $senderAddress;
    }

    /**
     * Writes the record down to the log of the implementing handler.
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    protected function write(array $record): void
    {
        $message = new \Swift_Message();
        $message->setSubject('Log')
            ->setFrom($this->senderAddress)
            ->setBody($this->twig->render('email/log.html.twig', $record))
            ->setTo($this->securityRecipients)
        ;

        $this->mailer->send($message);
    }
}
