<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use Doctrine\ORM\Mapping as ORM;
use Unilend\Traits\ConstantsAwareTrait;
use Unilend\Entity\Traits\TimestampableTrait;

/**
 * @ORM\Entity
 * @ORM\Table
 * @ORM\HasLifecycleCallbacks
 */
class MessageStatus
{
    use TimestampableTrait;
    use ConstantsAwareTrait;

    public const STATUS_UNREAD = 0;
    public const STATUS_READ = 1;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private int $status;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Message")
     * @ORM\JoinColumn(name="id_message", nullable=false)
     */
    private Message $message;

    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Staff")
     * @ORM\JoinColumn(name="id_recipient", nullable=false)
     */
    private Staff $recipient;

    /**
     * MessageStatus constructor.
     *
     * @param int     $status
     * @param Message $message
     * @param Staff   $recipient
     */
    public function __construct(int $status, Message $message, Staff $recipient)
    {
        if (!in_array($status, self::getPossibleStatuses(), true)) {
            throw new InvalidArgumentException(
                sprintf('%s is not a possible status for %s', $status, __CLASS__)
            );
        }
        $this->status    = $status;
        $this->message   = $message;
        $this->recipient = $recipient;
        $this->added     = new DateTimeImmutable();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @return Staff
     */
    public function getRecipient(): Staff
    {
        return $this->recipient;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public static function getPossibleStatuses(): array
    {
        return static::getConstants('STATUS_');
    }
}

