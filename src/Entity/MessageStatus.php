<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;;

use Unilend\Entity\Traits\PublicizeIdentityTrait;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;

/**
 * @ORM\Entity
 * @ORM\Table(
 *  name="message_status",
 *  indexes={
 *      @ORM\Index(name="idx_client", columns={"id_client"}),
 *      @ORM\Index(name="idx_company", columns={"id_company"}),
 *      @ORM\Index(name="idx_added", columns={"added"}),
 *  }
 * )
 * @ORM\Entity(repositoryClass="Unilend\Repository\MessageStatusRepository")
 * @ORM\HasLifecycleCallbacks
 */
class MessageStatus
{
    use PublicizeIdentityTrait;
    use TimestampableAddedOnlyTrait;

    public const STATUS_UNREAD = 0;
    public const STATUS_READ = 1;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Message", inversedBy="statuses")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id")
     */
    private Message $message;

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients", inversedBy="messageStatuses")
     * @ORM\JoinColumn(name="id_client", nullable=false)
     */
    private Clients $client;

    /**
     * @var Company
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Company")
     * @ORM\JoinColumn(name="id_company", nullable=false)
     */
    private Company $company;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private int $status;

    /**
     * MessageStatus constructor.
     */
    public function __construct()
    {
        $this->added = new \DateTimeImmutable();
    }

    /**
     * @param Message|null $message
     * @return MessageStatus
     */
    public function setMessage(?Message $message): MessageStatus
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
     * @param Clients|null $client
     * @return $this
     */
    public function setClient(?Clients $client): MessageStatus
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Clients
     */
    public function getClient(): Clients
    {
        return $this->client;
    }

    /**
     * @param Company|null $company
     * @return MessageStatus
     */
    public function setCompany(?Company $company): MessageStatus
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return Company
     */
    public function getCompany(): Company
    {
        return $this->company;
    }

    /**
     * @param int|null $status
     * @return MessageStatus
     */
    public function setStatus(?int $status): MessageStatus
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