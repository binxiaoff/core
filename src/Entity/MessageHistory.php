<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(
 *  name="message_history",
 *  indexes={
 *      @ORM\Index(name="idx_added", columns={"added"}),
 *  }
 * )
 * @ORM\HasLifecycleCallbacks
 */
class MessageHistory
{
    use TimestampableAddedOnlyTrait;

    public const STATUS_READ = 1;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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
     * @ORM\JoinColumn(name="id_maker", nullable=false)
     */
    private Staff $maker;

    /**
     * MessageStatus constructor.
     */
    public function __construct()
    {
        $this->added = new \DateTimeImmutable();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param Message|null $message
     *
     * @return MessageHistory
     */
    public function setMessage(?Message $message): MessageHistory
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @param Staff|null $maker
     * @return MessageHistory
     */
    public function setMaker(?Staff $maker): MessageHistory
    {
        $this->maker = $maker;

        return $this;
    }

    /**
     * @return Staff
     */
    public function getMaker(): Staff
    {
        return $this->maker;
    }

    /**
     * @param int|null $status
     * @return Messagehistory
     */
    public function setStatus(?int $status): Messagehistory
    {
        if (in_array($status, self::getPossibleStatuses(), true)) {
            $this->status = $status;

            return $this;
        }
        throw new InvalidArgumentException(
            sprintf('%s is not a possible status for %s', $status, __CLASS__)
        );
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