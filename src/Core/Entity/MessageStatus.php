<?php

declare(strict_types=1);

namespace Unilend\Core\Entity;

use ApiPlatform\Core\Annotation\{ApiFilter, ApiResource};
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\{Groups, MaxDepth};
use Symfony\Component\Validator\Constraints as Assert;
use Unilend\Core\Entity\Traits\TimestampableTrait;
use Unilend\Core\Traits\ConstantsAwareTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="core_message_status")
 * @ORM\HasLifecycleCallbacks
 *
 * @ApiResource(
 *  attributes={"pagination_enabled"=false},
 *  normalizationContext={"groups": {
 *     "messageStatus:read",
 *     "message:read"
 *  }},
 *  collectionOperations={
 *      "get"
 *  }
 * )
 * @ApiFilter(SearchFilter::class, properties={"status": "exact"})
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
     * @ORM\Column(type="smallint")
     *
     * @Assert\Choice(callback="getPossibleStatuses")
     *
     * @Groups({"messageStatus:read"})
     */
    private int $status;

    /**
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Message", inversedBy="messageStatuses")
     * @ORM\JoinColumn(name="id_message", referencedColumnName="id")
     *
     * @Groups({"messageStatus:read"})
     *
     * @MaxDepth(1)
     */
    private Message $message;

    /**
     * @var Staff
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Core\Entity\Staff")
     * @ORM\JoinColumn(name="id_recipient", nullable=false)
     *
     *
     * @Groups({"messageStatus:read"})
     */
    private Staff $recipient;

    /**
     * MessageStatus constructor.
     *
     * @param Message $message
     * @param Staff   $recipient
     * @param int     $status
     */
    public function __construct(Message $message, Staff $recipient, int $status = self::STATUS_UNREAD)
    {
        $this->message   = $message;
        $this->recipient = $recipient;
        $this->setStatus($status);
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
     * @param int $status
     *
     * @return $this
     */
    public function setStatus(int $status): MessageStatus
    {
        if (!in_array($status, self::getPossibleStatuses(), true)) {
            throw new InvalidArgumentException(
                sprintf('%s is not a possible status for %s', $status, __CLASS__)
            );
        }
        $this->status = $status;

        return $this;
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
