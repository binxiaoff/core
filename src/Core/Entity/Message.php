<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\{ApiResource};
use DateTimeImmutable;
use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;
use Unilend\Core\DTO\MessageInput;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_message")
 *
 * @ApiResource(
 *  attributes={
 *      "route_prefix"="/core"
 *  },
 *  normalizationContext={"groups": {
 *     "message:read",
 *     "messageStatus:read",
 *     "messageThread:read",
 *     "timestampable:read",
 *     "file:read",
 *     "fileVersion:read"
 *  }},
 *  collectionOperations={
 *       "post"={
 *          "input"=MessageInput::class
 *       }
 *  }
 * )
 */
class Message
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    public const FILE_TYPE_MESSAGE_ATTACHMENT = 'file_type_message_attachment';

    /**
     * @var MessageThread
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\MessageThread", inversedBy="messages")
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
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Staff")
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
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\MessageFile", mappedBy="message", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"message:read"})
     */
    private Collection $messageFiles;

    /**
     * @var MessageStatus[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Core\Entity\MessageStatus", mappedBy="message")
     *
     * @Groups({"message:read"})
     */
    private Collection $messageStatuses;

    /**
     * @var string|null
     *
     * @ORM\Column(length=36, nullable=true)
     *
     * @Groups({"message:read"})
     */
    private ?string $broadcast = null;

    /**
     * Message constructor.
     *
     * @param Staff         $sender
     * @param MessageThread $messageThread
     * @param string        $body
     */
    public function __construct(Staff $sender, MessageThread $messageThread, string $body)
    {
        $this->sender          = $sender;
        $this->messageThread   = $messageThread;
        $this->body            = $body;
        $this->messageFiles    = new ArrayCollection();
        $this->added           = new DateTimeImmutable();
        $this->messageStatuses = new ArrayCollection();
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
     * @return string|null
     */
    public function getBroadcast()
    {
        return $this->broadcast;
    }

    /**
     * @param string|null $broadcast
     *
     * @return $this
     */
    public function setBroadcast(string $broadcast = null): Message
    {
        if (null === $this->broadcast) {
            try {
                $this->broadcast = $broadcast ?: (string) (Uuid::uuid4());
            } catch (Throwable $e) {
                $this->broadcast = md5(uniqid('', false));
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isBroadcast(): bool
    {
        return null !== $this->broadcast;
    }
}
