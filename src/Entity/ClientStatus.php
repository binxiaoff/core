<?php

declare(strict_types=1);

namespace Unilend\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;
use Unilend\Entity\Interfaces\StatusInterface;
use Unilend\Entity\Interfaces\TraceableStatusAwareInterface;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * @ORM\Table(
 *     name="client_status",
 *     indexes={
 *         @ORM\Index(columns={"id_client"}, name="idx_client_status_id_client"),
 *         @ORM\Index(columns={"status"}, name="idx_client_status_status")
 *     }
 * )
 * @ORM\Entity(repositoryClass="Unilend\Repository\ClientStatusRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ClientStatus implements StatusInterface
{
    use ConstantsAwareTrait;
    use TimestampableAddedOnlyTrait;

    // The use is invited to our platform, the account is created in the database but the profil is not completed.
    public const STATUS_INVITED = 10;
    public const STATUS_CREATED = 20;

    public const GRANTED_LOGIN = [
        self::STATUS_INVITED,
        self::STATUS_CREATED,
    ];

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients", inversedBy="statuses")
     * @ORM\JoinColumn(name="id_client", referencedColumnName="id", nullable=false)
     */
    private $client;

    /**
     * @var int
     *
     * @Groups({"client_status:read"})
     *
     * @ORM\Column(type="smallint")
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @param Clients $clients
     * @param int     $status
     *
     * @throws \Exception
     */
    public function __construct(Clients $clients, int $status)
    {
        if (!in_array($status, static::getPossibleStatuses(), true)) {
            throw new InvalidArgumentException(
                sprintf('%s is not a possible status for %s', $status, __CLASS__)
            );
        }
        $this->status  = $status;
        $this->client  = $clients;
        $this->added   = new DateTimeImmutable();
    }

    /**
     * Get idClient.
     *
     * @return Clients
     */
    public function getClient(): Clients
    {
        return $this->client;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Clients|TraceableStatusAwareInterface
     */
    public function getAttachedObject()
    {
        return $this->getClient();
    }
}
