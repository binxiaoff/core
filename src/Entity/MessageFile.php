<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Unilend\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity(repositoryClass="Unilend\Repository\MessageFile")
 * @ORM\Table(
 *  name="message_file",
 *  indexes={
 *      @ORM\Index(name="idx_message", columns={"id_message"}),
 *      @ORM\Index(name="idx_added", columns={"added"}),
 *  }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class MessageFile
{
    public const ACCEPTED_FILE_TYPES = [
        'application/pdf',
        'application/vnd.ms-excel',
        'application/vnd.ms-powerpoint',
        'application/msword',
    ];

    use TimestampableAddedOnlyTrait;
    use PublicizeIdentityTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=50, nullable=false)
     * @Assert\Choice(callback="getTypes")
     */
    private $type;

    /**
     * @var File
     *
     * @ORM\OneToOne(targetEntity="Unilend\Entity\File", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_file", nullable=false, unique=true)
     */
    private $file;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Message", inversedBy="messageFiles")
     * @ORM\JoinColumn(name="id_message", nullable=false, onDelete="CASCADE")
     */
    private $message;

    /**
     * MessageFile constructor.
     * @param string $type
     * @param File $file
     * @param Message $message
     */
    public function __construct(string $type, File $file, Message $message)
    {
        if (!in_array($type, static::ACCEPTED_FILE_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('%s is not a possible type for %s', $type, __CLASS__)
            );
        }
        $this->type = $type;
        $this->file = $file;
        $this->message = $message;
        $this->added = new \DateTimeImmutable();
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @return string[]
     */
    public static function getTypes(): array
    {
        return self::ACCEPTED_FILE_TYPES;
    }
}