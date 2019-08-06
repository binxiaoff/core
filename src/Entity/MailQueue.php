<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

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
    public const STATUS_PENDING    = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_SENT       = 2;
    public const STATUS_ERROR      = -1;

    /**
     * @var \Unilend\Entity\MailTemplates
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\MailTemplates")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_mail_template", referencedColumnName="id_mail_template", nullable=false)
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
     * @var \DateTime
     *
     * @ORM\Column(name="to_send_at", type="datetime", nullable=true)
     */
    private $toSendAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     */
    private $sentAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id_queue", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idQueue;

    /**
     * @param MailTemplates $idMailTemplate
     *
     * @return MailQueue
     */
    public function setIdMailTemplate($idMailTemplate)
    {
        $this->idMailTemplate = $idMailTemplate;

        return $this;
    }

    /**
     * @return MailTemplates
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
     * @param \DateTime $toSendAt
     *
     * @return MailQueue
     */
    public function setToSendAt($toSendAt)
    {
        $this->toSendAt = $toSendAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getToSendAt()
    {
        return $this->toSendAt;
    }

    /**
     * @param \DateTime $sentAt
     *
     * @return MailQueue
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * @param \DateTime $updated
     *
     * @return MailQueue
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $added
     *
     * @return MailQueue
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @return int
     */
    public function getIdQueue()
    {
        return $this->idQueue;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (!$this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
