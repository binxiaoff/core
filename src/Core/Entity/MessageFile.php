<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\{ApiResource};
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Core\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Core\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ApiResource(
 *  normalizationContext={"groups": {
 *     "messageFile:read",
 *     "file:read",
 *  }},
 *  collectionOperations={
 *  },
 *  itemOperations={
 *      "get": {
 *          "security": "is_granted('view', object)"
 *     }
 *  }
 * )
 *
 * @ORM\Entity
 * @ORM\Table
 */
class MessageFile
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @var File
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\File", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_file", nullable=false)
     *
     * @Groups({"messageFile:read"})
     */
    private File $file;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Message", inversedBy="messageFiles")
     * @ORM\JoinColumn(name="id_message", onDelete="CASCADE", nullable=false)
     */
    private Message $message;

    /**
     * MessageFile constructor.
     *
     * @param File    $file
     * @param Message $message
     */
    public function __construct(File $file, Message $message)
    {
        $this->file    = $file;
        $this->message = $message;
        $this->added   = new DateTimeImmutable();
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
}
