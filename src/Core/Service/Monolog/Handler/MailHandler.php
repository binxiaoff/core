<?php

declare(strict_types=1);

namespace KLS\Core\Service\Monolog\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class MailHandler extends AbstractProcessingHandler
{
    private MailerInterface $mailer;
    /** @var mixed */
    private $securityRecipients;
    private Environment $twig;
    private string $senderAddress;

    public function __construct(
        MailerInterface $mailer,
        Environment $twig,
        $securityRecipients,
        int $level = Logger::CRITICAL, // @see https://github.com/symfony/monolog-bundle/issues/322
        bool $bubble = true,
        string $senderAddress = 'support@kls-platform.com'
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
     * @throws LoaderError|RuntimeError|SyntaxError|TransportExceptionInterface
     */
    protected function write(array $record): void
    {
        $message = new Email();
        $message->subject('Log')
            ->setFrom($this->senderAddress)
            ->setBody($this->twig->render('email/log.html.twig', $record))
            ->setTo($this->securityRecipients)
        ;

        $this->mailer->send($message);
    }
}
