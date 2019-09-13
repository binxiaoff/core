<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsStatus.
 *
 * @ORM\Table(
 *     name="clients_status",
 *     indexes={
 *         @ORM\Index(columns={"clients_id"}, name="idx_clients_status_clients_id"),
 *         @ORM\Index(columns={"status"}, name="idx_clients_status_status")
 *     }
 * )
 * @ORM\Entity(repositoryClass="Unilend\Repository\ClientsStatusRepository")
 */
class ClientsStatus extends AbstractStatus
{
    public const STATUS_CREATED   = 10;
    public const STATUS_VALIDATED = 20;
    public const STATUS_BLOCKED   = 30;
    public const STATUS_CLOSED    = 100;

    public const GRANTED_LOGIN = [
        self::STATUS_CREATED,
        self::STATUS_VALIDATED,
    ];

    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients", inversedBy="statuses")
     * @ORM\JoinColumn(name="clients_id", referencedColumnName="id_client", nullable=false, onDelete="CASCADE")
     */
    private $clients;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", length=16777215, nullable=true)
     */
    private $content;

    /**
     * ClientsStatus constructor.
     *
     * @param Clients     $clients
     * @param int         $status
     * @param string|null $content
     *
     * @throws \Exception
     */
    public function __construct(Clients $clients, int $status, string $content = null)
    {
        parent::__construct($status);
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
}
