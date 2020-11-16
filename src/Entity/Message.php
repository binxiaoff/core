<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\{ArrayCollection, Collection};

use Unilend\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(
 *  name="message",
 *  indexes={
 *      @ORM\Index(name="idx_sender", columns={"sender"}),
 *      @ORM\Index(name="idx_added", columns={"added"}),
 *      @ORM\Index(name="idx_thread_message", columns={"id_message_thread"}),
 *  }
 * )
 */
class Message
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @var MessageThread
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\MessageThread", inversedBy="messages")
     * @ORM\JoinColumn(name="id_message_thread", referencedColumnName="id")
     */
    private MessageThread $messageThread;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumn(name="sender", referencedColumnName="id")
     *
     * @Assert\NotBlank
     * @Assert\Valid
     */
    private Clients $sender;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text", length=16777215, nullable=false)
     *
     * @Assert\NotBlank
     */
    protected string $body;

    /**
     * @var MessageStatus
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\MessageStatus", mappedBy="message")
     */
    private Collection $statuses;

    /**
     * Message constructor.
     */
    public function __construct()
    {
        $this->statuses = new ArrayCollection();
    }

    /**
     * @param Clients|null $sender
     * @return Message
     */
    public function setSender(?Clients $sender): Message
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getSender(): Clients
    {
        return $this->sender;
    }

    /**
     * @param MessageThread|null $messageThread
     * @return Message
     */
    public function setMessageThread(?MessageThread $messageThread): Message
    {
        $this->messageThread = $messageThread;

        return $this;
    }

    /**
     * @return MessageThread
     */
    public function getMessageThread(): MessageThread
    {
        return $this->messageThread;
    }

    /**
     * @param string|null $body
     * @return Message
     */
    public function setBody(?string $body): Message
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param Collection|null $statuses
     * @return Message
     */
    public function setMessages(?Collection $statuses): Message
    {
        $this->statuses = $statuses;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getStatuses()
    {
        return $this->statuses;
    }

    /**
     * @param MessageStatus $status
     * @return Message
     */
    public function addStatus(MessageStatus $status): Message
    {
        if (!$this->statuses->contains($status)) {
            $this->statuses->add($status);
        }
        return $this;
    }

    /**
     * @param MessageStatus $status
     * @return Message
     */
    public function removeStatus(MessageStatus $status): Message
    {
        if ($this->statuses->contains($status)) {
            $this->statuses->remove($status);
        }
        return $this;
    }
}