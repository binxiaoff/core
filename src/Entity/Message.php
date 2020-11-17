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
 *  indexes={
 *      @ORM\Index(name="idx_added", columns={"added"}),
 *  }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class Message
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @var MessageThread
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\MessageThread", inversedBy="messages")
     * @ORM\JoinColumn(name="id_message_thread", nullable=false)
     */
    private MessageThread $messageThread;

    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Staff")
     * @ORM\JoinColumn(name="id_sender", nullable=false)
     *
     * @Assert\NotBlank
     * @Assert\Valid
     */
    private Staff $sender;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=16777215, nullable=false)
     *
     * @Assert\NotBlank
     */
    protected string $body;

    /**
     * @var MessageFile[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\MessageFile", mappedBy="message", cascade={"persist"}, orphanRemoval=true)
     */
    private Collection $messageFiles;

    /**
     * Message constructor.
     */
    public function __construct()
    {
        $this->added = new \DateTimeImmutable();
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
     *
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
     *
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
     * @return MessageFile[]|Collection
     */
    public function getMessageFiles(): Collection
    {
        return $this->messageFiles;
    }

    /**
     * @param MessageFile $messageFile
     * @return $this
     */
    public function addMessageFile(MessageFile $messageFile): Message
    {
        if (!$this->messageFiles->contains($messageFile)) {
            $this->messageFiles->add($messageFile);
        }
        return $this;
    }
}
