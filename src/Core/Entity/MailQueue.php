<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Swift_Message;
use Swift_Mime_SimpleMessage;
use UnexpectedValueException;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Core\SwiftMailer\MailjetMessage;

/**
 * @ORM\Table(name="mail_queue", indexes={
 *    @ORM\Index(name="status", columns={"status"}),
 *    @ORM\Index(name="idx_mail_queue_sent_at", columns={"sent_at"})
 * })
 * @ORM\Entity
 */
class MailQueue
{
    use TimestampableAddedOnlyTrait;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_ERROR = 'error';

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_queue", type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string")
     */
    private string $status;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="sent_at", type="datetime_immutable", nullable=true)
     */
    private DateTimeImmutable $sentAt;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="scheduled_at", type="datetime_immutable")
     */
    private DateTimeImmutable $scheduledAt;

    /**
     * @var array
     *
     * @ORM\Column(type="json")
     */
    private array $recipients;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private string $serialized;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private string $hash;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $mailjetTemplateId;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=65535, nullable=true)
     */
    private string $errorMessage;

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param DateTimeImmutable|null   $scheduledAt
     */
    public function __construct(Swift_Mime_SimpleMessage $message, DateTimeImmutable $scheduledAt = null)
    {
        $this->serialized = serialize($message);
        $this->hash = hash('sha256', $this->serialized);
        if ($message instanceof MailjetMessage) {
            $this->mailjetTemplateId = $message->getTemplateId();
        }
        $this->recipients = $message->getTo();
        $this->status = static::STATUS_PENDING;
        $this->scheduledAt = $scheduledAt ?? new DateTimeImmutable();
        $this->added = new DateTimeImmutable();
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getSentAt(): DateTimeImmutable
    {
        return $this->sentAt;
    }

    /**
     * @return MailQueue
     */
    public function succeed(): MailQueue
    {
        $this->status = static::STATUS_SENT;
        $this->sentAt = new DateTimeImmutable();

        return $this;
    }

    /**
     * @param string|null $errorMessage
     *
     * @return MailQueue
     */
    public function fail(?string $errorMessage = null): MailQueue
    {
        $this->status = static::STATUS_ERROR;

        if ($errorMessage) {
            $this->errorMessage = $errorMessage;
        }

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getScheduledAt(): DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    /**
     * @param DateTimeImmutable $scheduledAt
     *
     * @return MailQueue
     */
    public function setScheduledAt(DateTimeImmutable $scheduledAt): MailQueue
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    /**
     * @return array
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * @return int|null
     */
    public function getMailjetTemplateId(): ?int
    {
        return $this->mailjetTemplateId;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * @return Swift_Mime_SimpleMessage
     *
     * @throw UnexpectedValueException
     */
    public function getMessage(): Swift_Mime_SimpleMessage
    {
        if (hash('sha256', $this->serialized) !== $this->hash) {
            throw new UnexpectedValueException('The serialized message might have been altered');
        }

        return unserialize($this->serialized, ['allowed_classes' => true]);
    }
}
