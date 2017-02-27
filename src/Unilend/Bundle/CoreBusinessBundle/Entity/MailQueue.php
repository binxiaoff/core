<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailQueue
 *
 * @ORM\Table(name="mail_queue", indexes={@ORM\Index(name="status", columns={"status"}), @ORM\Index(name="recipient", columns={"recipient", "id_mail_template"}), @ORM\Index(name="id_client", columns={"id_client"})})
 * @ORM\Entity
 */
class MailQueue
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_mail_template", type="integer", nullable=false)
     */
    private $idMailTemplate;

    /**
     * @var string
     *
     * @ORM\Column(name="serialized_variables", type="text", length=65535, nullable=true)
     */
    private $serializedVariables;

    /**
     * @var string
     *
     * @ORM\Column(name="attachments", type="text", length=65535, nullable=false)
     */
    private $attachments;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient", type="string", length=191, nullable=false)
     */
    private $recipient;

    /**
     * @var string
     *
     * @ORM\Column(name="reply_to", type="string", length=191, nullable=false)
     */
    private $replyTo;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_client", type="integer", nullable=true)
     */
    private $idClient;

    /**
     * @var string
     *
     * @ORM\Column(name="serialized_reponse", type="text", length=65535, nullable=true)
     */
    private $serializedReponse;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
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
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_queue", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idQueue;



    /**
     * Set idMailTemplate
     *
     * @param integer $idMailTemplate
     *
     * @return MailQueue
     */
    public function setIdMailTemplate($idMailTemplate)
    {
        $this->idMailTemplate = $idMailTemplate;

        return $this;
    }

    /**
     * Get idMailTemplate
     *
     * @return integer
     */
    public function getIdMailTemplate()
    {
        return $this->idMailTemplate;
    }

    /**
     * Set serializedVariables
     *
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
     * Get serializedVariables
     *
     * @return string
     */
    public function getSerializedVariables()
    {
        return $this->serializedVariables;
    }

    /**
     * Set attachments
     *
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
     * Get attachments
     *
     * @return string
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Set recipient
     *
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
     * Get recipient
     *
     * @return string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * Set replyTo
     *
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
     * Get replyTo
     *
     * @return string
     */
    public function getReplyTo()
    {
        return $this->replyTo;
    }

    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return MailQueue
     */
    public function setIdClient($idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return integer
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set serializedReponse
     *
     * @param string $serializedReponse
     *
     * @return MailQueue
     */
    public function setSerializedReponse($serializedReponse)
    {
        $this->serializedReponse = $serializedReponse;

        return $this;
    }

    /**
     * Get serializedReponse
     *
     * @return string
     */
    public function getSerializedReponse()
    {
        return $this->serializedReponse;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return MailQueue
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set toSendAt
     *
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
     * Get toSendAt
     *
     * @return \DateTime
     */
    public function getToSendAt()
    {
        return $this->toSendAt;
    }

    /**
     * Set sentAt
     *
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
     * Get sentAt
     *
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * Set updated
     *
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
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set added
     *
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
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Get idQueue
     *
     * @return integer
     */
    public function getIdQueue()
    {
        return $this->idQueue;
    }
}
