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
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\MailTemplate")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_mail_template", referencedColumnName="id", nullable=false)
     * })
     */
    private $idMailTemplate;

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
    private $idClient;

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
     * @ORM\Column(name="id_queue", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idQueue;

    /**
     * @param MailTemplate $idMailTemplate
     *
     * @return MailQueue
     */
    public function setIdMailTemplate($idMailTemplate): MailQueue
    {
        $this->idMailTemplate = $idMailTemplate;

        return $this;
    }

    /**
     * @return MailTemplate
     */
    public function getIdMailTemplate()
    {
        return $this->idMailTemplate;
    }

    /**
     * @param string $serializedVariables
     *
     * @return MailQueue
     */
    public function setSerializedVariables($serializedVariables)
    {
        $this->serializedVariables = $serializedVariables;

        return $this;
    }

    /**
     * @return string
     */
    public function getSerializedVariables()
    {
        return $this->serializedVariables;
    }

    /**
     * @param string $attachments
     *
     * @return MailQueue
     */
    public function setAttachments($attachments)
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * @return string
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param string $recipient
     *
     * @return MailQueue
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * @return string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @param string $replyTo
     *
     * @return MailQueue
     */
    public function setReplyTo($replyTo)
    {
        $this->replyTo = $replyTo;

        return $this;
    }

    /**
     * @return string
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * @param int $idClient
     *
     * @return MailQueue
     */
    public function setIdClient($idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * @param int $idMessageMailjet
     *
     * @return MailQueue
     */
    public function setIdMessageMailjet($idMessageMailjet)
    {
        $this->idMessageMailjet = $idMessageMailjet;

        return $this;
    }

    /**
     * @return int
     */
    public function getIdMessageMailjet()
    {
        return $this->idMessageMailjet;
    }

    /**
     * @param string $errorMailjet
     *
     * @return MailQueue
     */
    public function setErrorMailjet($errorMailjet)
    {
        $this->errorMailjet = $errorMailjet;

        return $this;
    }

    /**
     * @return string
     */
    public function getErrorMailjet()
    {
        return $this->errorMailjet;
    }

    /**
     * @param int $status
     *
     * @return MailQueue
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param DateTimeImmutable $toSendAt
     *
     * @return MailQueue
     */
    public function setToSendAt($toSendAt)
    {
        $this->toSendAt = $toSendAt;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getToSendAt()
    {
        return $this->toSendAt;
    }

    /**
     * @param DateTimeImmutable $sentAt
     *
     * @return MailQueue
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * @return int
     */
    public function getIdQueue()
    {
        return $this->idQueue;
    }
}
