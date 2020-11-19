<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
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
     * @ORM\OneToOne(targetEntity="Unilend\Entity\File", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="id_file", unique=true, nullable=false)
     */
    private File $file;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Message", inversedBy="messageFiles")
     * @ORM\JoinColumn(name="id_message", onDelete="CASCADE", nullable=false)
     */
    private Message $message;

    /**
     * MessageFile constructor.
     *
     * @param File $file
     * @param Message $message
     */
    public function __construct(File $file, Message $message)
    {
        $this->file = $file;
        $this->message = $message;
        $this->added = new DateTimeImmutable();
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