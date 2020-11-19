<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Unilend\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table
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
     * @ORM\Column(type="text", length=16777215)
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
     *
     * @param Staff         $sender
     * @param MessageThread $messageThread
     * @param string        $body
     */
    public function __construct(Staff $sender, MessageThread $messageThread, string $body)
    {
        $this->sender = $sender;
        $this->messageThread = $messageThread;
        $this->body = $body;
        $this->messageFiles = new ArrayCollection();
        $this->added = new DateTimeImmutable();
    }

    /**
     * @return Staff
     */
    public function getSender(): Staff
    {
        return $this->sender;
    }

    /**
     * @return MessageThread
     */
    public function getMessageThread(): MessageThread
    {
        return $this->messageThread;
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
     *
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
