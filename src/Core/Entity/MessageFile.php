<?php

declare(strict_types=1);

namespace KLS\Core\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use KLS\Core\Entity\Traits\PublicizeIdentityTrait;
use KLS\Core\Entity\Traits\TimestampableAddedOnlyTrait;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_message_file")
 */
class MessageFile
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\File", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_file", nullable=false)
     *
     * @Groups({"messageFile:read"})
     */
    private File $file;

    /**
     * @ORM\ManyToOne(targetEntity="KLS\Core\Entity\Message", inversedBy="messageFiles")
     * @ORM\JoinColumn(name="id_message", onDelete="CASCADE", nullable=false)
     */
    private Message $message;

    public function __construct(File $file, Message $message)
    {
        $this->file    = $file;
        $this->message = $message;
        $this->added   = new DateTimeImmutable();
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
