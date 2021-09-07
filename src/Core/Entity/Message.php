<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\DTO\MessageInput;
use KLS\Core\Entity\Interfaces\FileTypesAwareInterface;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_message")
 *
 * @ApiResource(
 *     normalizationContext={"groups": {
 *         "message:read",
 *         "user:read",
 *         "staff:read",
 *         "company:read",
 *         "timestampable:read",
 *         "file:read",
 *         "fileVersion:read"
 *     }},
 *     collectionOperations={
 *         "post": {
 *             "input": MessageInput::class
 *         }
 *     }
 * )
 */
class Message implements FileTypesAwareInterface
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    public const FILE_TYPE_MESSAGE_ATTACHMENT = 'file_type_message_attachment';

    /**
     * @ORM\Column(type="text", length=16777215)
     *
     * @Groups({"message:read"})
     *
     * @Assert\NotBlank
     */
    protected string $body;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\MessageThread", inversedBy="messages")
     * @ORM\JoinColumn(name="id_message_thread", nullable=false, onDelete="CASCADE")
     *
     * @Groups({"message:read"})
     *
     * @MaxDepth(1)
     */
    private MessageThread $messageThread;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Staff")
     * @ORM\JoinColumn(name="id_sender", nullable=false)
     *
     * @Groups({"message:read"})
     *
     * @Assert\NotBlank
     * @Assert\Valid
     */
    private Staff $sender;

    /**
     * @var MessageFile[]|Collection
     *
     * @ORM\OneToMany(targetEntity="KLS\Core\Entity\MessageFile", mappedBy="message", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"message:read"})
     */
    private Collection $messageFiles;

    /**
     * @var MessageStatus[]|Collection
     *
     * @ORM\OneToMany(targetEntity="KLS\Core\Entity\MessageStatus", mappedBy="message")
     *
     * @Groups({"message:read"})
     */
    private Collection $messageStatuses;

    /**
     * @ORM\Column(length=36, nullable=true)
     *
     * @Groups({"message:read"})
     */
    private ?string $broadcast = null;

    public function __construct(Staff $sender, MessageThread $messageThread, string $body)
    {
        $this->sender          = $sender;
        $this->messageThread   = $messageThread;
        $this->body            = $body;
        $this->messageFiles    = new ArrayCollection();
        $this->added           = new DateTimeImmutable();
        $this->messageStatuses = new ArrayCollection();
    }

    public static function getFileTypes(): array
    {
        return [static::FILE_TYPE_MESSAGE_ATTACHMENT];
    }

    public function getSender(): Staff
    {
        return $this->sender;
    }

    public function getMessageThread(): MessageThread
    {
        return $this->messageThread;
    }

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
     * @return $this
     */
    public function setBroadcast(string $broadcast = null): Message
    {
        if (null === $this->broadcast) {
            try {
                $this->broadcast = $broadcast ?: (string) (Uuid::uuid4());
            } catch (Throwable $e) {
                $this->broadcast = \md5(\uniqid('', false));
            }
        }

        return $this;
    }

    public function isBroadcast(): bool
    {
        return null !== $this->broadcast;
    }
}
