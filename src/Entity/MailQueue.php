<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Table(name="mail_queue", indexes={
 *     @ORM\Index(name="status", columns={"status"}),
 *     @ORM\Index(name="recipient", columns={"recipient", "id_mail_template"}),
 *     @ORM\Index(name="id_client", columns={"id_client"}),
 *     @ORM\Index(name="idx_mail_queue_sent_at", columns={"sent_at"}),
 *     @ORM\Index(name="id_message_mailjet", columns={"id_message_mailjet"})
 * })
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Unilend\Repository\MailQueueRepository")
 */
class MailQueue
{
    use TimestampableTrait;

    public const STATUS_PENDING    = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_SENT       = 2;
    public const STATUS_ERROR      = -1;

    /**
     * @var MailTemplate
     *
     * @ORM\ManyToOne(targetEntity="MailTemplate")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_mail_template", referencedColumnName="id", nullable=false)
     * })
     */
    private $mailTemplate;

    /**
     * @var string
     *
     * @ORM\Column(name="serialized_variables", type="text", length=16777215, nullable=true)
     */
    private $serializedVariables;

    /**
     * Attachments path separated by ;.
     *
     * @var string
     *
     * @ORM\Column(name="attachments", type="text", length=65535)
     */
    private $attachments;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient", type="string", length=191)
     */
    private $recipient;

    /**
     * @var string
     *
     * @ORM\Column(name="reply_to", type="string", length=191)
     */
    private $replyTo;

    /**
     * @var int
     *
     * @ORM\Column(name="id_client", type="integer", nullable=true)
     */
    private $client;

    /**
     * @var string
     *
     * @ORM\Column(name="id_message_mailjet", type="bigint", nullable=true)
     */
    private $idMessageMailjet;

    /**
     * @var string
     *
     * @ORM\Column(name="error_mailjet", type="string", length=256, nullable=true)
     */
    private $errorMailjet;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="to_send_at", type="date_immutable", nullable=true)
     */
    private $toSendAt;

    /**
     * @var DateTimeImmutable
     *
     * @ORM\Column(name="sent_at", type="date_immutable", nullable=true)
     */
    private $sentAt;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_queue", type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * MailQueue constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->added = new DateTimeImmutable();
    }

    /**
     * @param MailTemplate $mailTemplate
     *
     * @return MailQueue
     */
    public function setMailTemplate($mailTemplate): MailQueue
    {
        $this->mailTemplate = $mailTemplate;

        return $this;
    }

    /**
     * @return MailTemplate
     */
    public function getMailTemplate(): MailTemplate
    {
        return $this->mailTemplate;
    }

    /**
     * @param string $serializedVariables
     *
     * @return MailQueue
     */
    public function setSerializedVariables($serializedVariables): MailQueue
    {
        $this->serializedVariables = $serializedVariables;

        return $this;
    }

    /**
     * @return string
     */
    public function getSerializedVariables(): string
    {
        return $this->serializedVariables;
    }

    /**
     * @param string $attachments
     *
     * @return MailQueue
     */
    public function setAttachments($attachments): MailQueue
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * @return string
     */
    public function getAttachments(): string
    {
        return $this->attachments;
    }

    /**
     * @param string $recipient
     *
     * @return MailQueue
     */
    public function setRecipient($recipient): MailQueue
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }

    /**
     * @param string $replyTo
     *
     * @return MailQueue
     */
    public function setReplyTo($replyTo): MailQueue
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    /**
     * @return string
     */
    public function getReplyTo(): string
    {
        return $this->replyTo;
    }

    /**
     * @param int $client
     *
     * @return MailQueue
     */
    public function setClient($client): MailQueue
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return int
     */
    public function getClient(): int
    {
        return $this->client;
    }

    /**
     * @param int $idMessageMailjet
     *
     * @return MailQueue
     */
    public function setIdMessageMailjet($idMessageMailjet): MailQueue
    {
        $this->idMessageMailjet = $idMessageMailjet;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdMessageMailjet(): string
    {
        return $this->idMessageMailjet;
    }

    /**
     * @param string $errorMailjet
     *
     * @return MailQueue
     */
    public function setErrorMailjet($errorMailjet): MailQueue
    {
        $this->errorMailjet = $errorMailjet;

        return $this;
    }

    /**
     * @return string
     */
    public function getErrorMailjet(): string
    {
        return $this->errorMailjet;
    }

    /**
     * @param int $status
     *
     * @return MailQueue
     */
    public function setStatus($status): MailQueue
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param DateTimeImmutable $toSendAt
     *
     * @return MailQueue
     */
    public function setToSendAt($toSendAt): MailQueue
    {
        $this->toSendAt = $toSendAt;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getToSendAt(): DateTimeImmutable
    {
        return $this->toSendAt;
    }

    /**
     * @param DateTimeImmutable $sentAt
     *
     * @return MailQueue
     */
    public function setSentAt($sentAt): MailQueue
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getSentAt(): DateTimeImmutable
    {
        return $this->sentAt;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
