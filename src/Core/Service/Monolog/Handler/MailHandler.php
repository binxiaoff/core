<?php

declare(strict_types=1);

namespace Unilend\Core\Service\Monolog\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Swift_Mailer;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class MailHandler extends AbstractProcessingHandler
{
    /** @var Swift_Mailer */
    private Swift_Mailer $mailer;
    /** @var mixed */
    private $securityRecipients;

    /**
     * @var Environment
     */
    private Environment $twig;

    /**
     * @param Swift_Mailer $mailer
     * @param Environment  $twig
     * @param mixed        $securityRecipients
     * @param int          $level
     * @param bool         $bubble
     */
    public function __construct(
        Swift_Mailer $mailer,
        Environment $twig,
        $securityRecipients,
        $level = Logger::CRITICAL, // @see https://github.com/symfony/monolog-bundle/issues/322
        $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->mailer                  = $mailer;
        $this->securityRecipients      = $securityRecipients;
        $this->twig = $twig;
    }

    /**
     * Writes the record down to the log of the implementing handler.
     *
     * @param array $record
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    protected function write(array $record): void
    {
        $message = new \Swift_Message();
        $message->setSubject('Log')
            ->setBody($this->twig->render('email/log.html.twig', $record))
            ->setTo($this->securityRecipients);

        $this->mailer->send($message);
    }
}
