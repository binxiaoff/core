<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\IdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use KLS\Core\Mailer\MailjetMessage;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message as SymfonyMessage;

/**
 * @ORM\Table(name="core_mail_log", indexes={
 *     @ORM\Index(name="status", columns={"status"}),
 *     @ORM\Index(name="idx_mail_queue_sent_at", columns={"sent_at"}),
 *     @ORM\Index(name="idx_mail_log_message_id", columns={"message_id"})
 * })
 * @ORM\Entity
 */
class MailLog
{
    use TimestampableAddedOnlyTrait;
    use IdentityTrait;

    public const STATUS_QUEUED = 'queued';
    // The real status is "about to be sent" instead of "sent". See Symfony\Component\Mailer\Event\MessageEvent
    // and KLS\Core\EventSubscriber\Mailer\PreSendMailSubscriber
    public const STATUS_SENT = 'sent';

    /**
     * @ORM\Column(name="status", type="string")
     */
    private string $status;

    /**
     * @ORM\Column(name="sent_at", type="datetime_immutable", nullable=true)
     */
    private DateTimeImmutable $sentAt;

    /**
     * @ORM\Column(type="json")
     */
    private array $recipients;

    /**
     * @ORM\Column(type="text")
     */
    private string $serialized;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $mailjetTemplateId;

    /**
     * @ORM\Column(length=100)
     */
    private string $transport;

    /**
     * @ORM\Column(length=60, nullable=true)
     */
    private ?string $messageId;

    public function __construct(SymfonyMessage $message, string $transport)
    {
        $this->serialized = \serialize($message);
        if ($message instanceof MailjetMessage) {
            $this->mailjetTemplateId = $message->getTemplateId();
        }
        if ($message instanceof Email) {
            $this->recipients = \array_map(fn ($address) => $address->toString(), $message->getTo());
        }

        $this->messageId = self::findMessageIdFromMessage($message);
        $this->transport = $transport; // to see if it contains the api key and secret
        $this->status    = static::STATUS_QUEUED;
        $this->added     = new DateTimeImmutable();
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSentAt(): DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function succeed(): MailLog
    {
        $this->status = static::STATUS_SENT;
        $this->sentAt = new DateTimeImmutable();

        return $this;
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getMailjetTemplateId(): ?int
    {
        return $this->mailjetTemplateId;
    }

    public function setTransport(string $transport): MailLog
    {
        $this->transport = $transport;

        return $this;
    }

    public static function findMessageIdFromMessage(SymfonyMessage $message): ?string
    {
        $messageIdHeader = $message->getHeaders()->get('Message-ID');

        return $messageIdHeader ? $messageIdHeader->getBodyAsString() : null;
    }
}
