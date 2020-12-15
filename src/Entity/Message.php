<?php

declare(strict_types=1);

namespace Unilend\Entity;

use ApiPlatform\Core\Annotation\{ApiResource};
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\DTO\MessageInput;
use Unilend\DTO\MessageBroadcastInput;
use Unilend\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ApiResource(
 *  normalizationContext={"groups": {
 *     "message:read",
 *     "messageStatus:read",
 *     "messageThread:read",
 *     "client:read",
 *     "staff:read",
 *     "company:read",
 *     "timestampable:read",
 *     "file:read",
 *     "fileVersion:read"
 *  }},
 *  collectionOperations={
 *       "post"={
 *          "input"=MessageInput::class
 *       }
 *  },
 *  itemOperations={
 *      "get": {
 *          "security": "is_granted('view', object)"
 *     }
 *  }
 * )
 *
 * @ORM\Table
 * @ORM\Entity
 */
class Message
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    public const FILE_TYPE_MESSAGE_ATTACHMENT = 'file_type_message_attachment';

    /**
     * @var MessageThread
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\MessageThread", cascade={"persist"}, inversedBy="messages")
     * @ORM\JoinColumn(name="id_message_thread", nullable=false)
     *
     * @Groups({"message:read"})
     *
     * @MaxDepth(1)
     */
    private MessageThread $messageThread;

    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Staff")
     * @ORM\JoinColumn(name="id_sender", nullable=false)
     *
     * @Groups({"message:read"})
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
     * @Groups({"message:read"})
     *
     * @Assert\NotBlank
     */
    protected string $body;

    /**
     * @var MessageFile[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\MessageFile", mappedBy="message", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"message:read"})
     */
    private Collection $messageFiles;

    /**
     * @var MessageStatus[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\MessageStatus", mappedBy="message")
     */
    private Collection $messageStatuses;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     *
     * @Groups({"message:read"})
     */
    private bool $broadcast;

    /**
     * Message constructor.
     *
     * @param Staff         $sender
     * @param MessageThread $messageThread
     * @param string        $body
     * @param bool          $broadcast
     */
    public function __construct(Staff $sender, MessageThread $messageThread, string $body, bool $broadcast = false)
    {
        $this->sender          = $sender;
        $this->messageThread   = $messageThread;
        $this->body            = $body;
        $this->messageFiles    = new ArrayCollection();
        $this->added           = new DateTimeImmutable();
        $this->messageStatuses = new ArrayCollection();
        $this->broadcast       = $broadcast;
    }

    /**
     * @return array
     */
    public static function getFileTypes()
    {
        return [static::FILE_TYPE_MESSAGE_ATTACHMENT];
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
     * @return Message
     */
    public function addMessageFile(MessageFile $messageFile): Message
    {
        if (!$this->messageFiles->contains($messageFile)) {
            $this->messageFiles->add($messageFile);
        }

        return $this;
    }

    /**
     * @return MessageStatus[]|Collection
     */
    public function getMessageStatuses(): Collection
    {
        return $this->messageStatuses;
    }

    /**
     * @return bool
     */
    public function isBroadcast(): bool
    {
        return $this->broadcast;
    }
}
