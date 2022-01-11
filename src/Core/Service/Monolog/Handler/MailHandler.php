<?php

declare(strict_types=1);

namespace KLS\Core\Service\Monolog\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class MailHandler extends AbstractProcessingHandler
{
    private MailerInterface $mailer;
    /** @var mixed */
    private $securityRecipients;
    private string $senderAddress;

    public function __construct(
        MailerInterface $mailer,
        $securityRecipients,
        int $level = Logger::CRITICAL, // @see https://github.com/symfony/monolog-bundle/issues/322
        bool $bubble = true,
        string $senderAddress = 'support@kls-platform.com'
    ) {
        parent::__construct($level, $bubble);
        $this->mailer             = $mailer;
        $this->securityRecipients = $securityRecipients;
        $this->senderAddress      = $senderAddress;
    }

    /**
     * Writes the record down to the log of the implementing handler.
     *
     * @throws TransportExceptionInterface
     */
    protected function write(array $record): void
    {
        $message = new TemplatedEmail();
        $message->subject('Log')
            ->from($this->senderAddress)
            ->to($this->securityRecipients)
            ->htmlTemplate('email/log.html.twig')
            ->context($record)
        ;

        $this->mailer->send($message);
    }
}
