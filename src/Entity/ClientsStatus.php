<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Unilend\Entity\Interfaces\StatusInterface;
use Unilend\Entity\Traits\TimestampableAddedOnlyTrait;
use Unilend\Traits\ConstantsAwareTrait;

/**
 * ClientsStatus.
 *
 * @ORM\Table(
 *     name="clients_status",
 *     indexes={
 *         @ORM\Index(columns={"id_client"}, name="idx_clients_status_id_client"),
 *         @ORM\Index(columns={"status"}, name="idx_clients_status_status")
 *     }
 * )
 * @ORM\Entity(repositoryClass="Unilend\Repository\ClientsStatusRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ClientsStatus implements StatusInterface
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
     * @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     */
    private $clients;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", length=16777215, nullable=true)
     */
    private $content;

    /**
     * @var int
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
     * ClientsStatus constructor.
     *
     * @param Clients     $clients
     * @param int         $status
     * @param string|null $content
     */
    public function __construct(Clients $clients, int $status, string $content = null)
    {
        if (!in_array($status, static::getPossibleStatuses(), true)) {
            throw new InvalidArgumentException(
                sprintf('%s is not a possible status for %s', $status, __CLASS__)
            );
        }
        $this->status  = $status;
        $this->clients = $clients;
        $this->content = $content;
    }

    /**
     * Get idClient.
     *
     * @return Clients
     */
    public function getClients(): Clients
    {
        return $this->clients;
    }

    /**
     * Set content.
     *
     * @param string|null $content
     *
     * @return ClientsStatus
     */
    public function setContent(?string $content): ClientsStatus
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @return int
     */
    public function getId(): int
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
}
