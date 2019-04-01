<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsStatusHistory
 *
 * @ORM\Table(name="clients_status_history", indexes={@ORM\Index(name="id_client", columns={"id_client"}), @ORM\Index(name="id_user", columns={"id_user"}), @ORM\Index(name="idx_clients_status_history_id_status", columns={"id_status"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ClientsStatusHistoryRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ClientsStatusHistory
{
    /**
     * @var Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @var ClientsStatus
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ClientsStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_status", referencedColumnName="id", nullable=false)
     * })
     */
    private $idStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", length=16777215, nullable=true)
     */
    private $content;

    /**
     * @var Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id_user", nullable=false)
     * })
     */
    private $idUser;

    /**
     * @var int
     *
     * @ORM\Column(name="numero_relance", type="integer", nullable=true)
     */
    private $numeroRelance;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set idClient
     *
     * @param Clients $idClient
     *
     * @return ClientsStatusHistory
     */
    public function setIdClient(Clients $idClient): ClientsStatusHistory
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return Clients
     */
    public function getIdClient(): Clients
    {
        return $this->idClient;
    }

    /**
     * Set idStatus
     *
     * @param ClientsStatus $idStatus
     *
     * @return ClientsStatusHistory
     */
    public function setIdStatus(ClientsStatus $idStatus): ClientsStatusHistory
    {
        $this->idStatus = $idStatus;

        return $this;
    }

    /**
     * Get ClientsStatus
     *
     * @return ClientsStatus
     */
    public function getIdStatus(): ClientsStatus
    {
        return $this->idStatus;
    }

    /**
     * Set content
     *
     * @param string|null $content
     *
     * @return ClientsStatusHistory
     */
    public function setContent(?string $content): ClientsStatusHistory
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Set idUser
     *
     * @param Users $idUser
     *
     * @return ClientsStatusHistory
     */
    public function setIdUser(Users $idUser): ClientsStatusHistory
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return Users
     */
    public function getIdUser(): Users
    {
        return $this->idUser;
    }

    /**
     * Set numeroRelance
     *
     * @param int|null $numeroRelance
     *
     * @return ClientsStatusHistory
     */
    public function setNumeroRelance(?int $numeroRelance): ClientsStatusHistory
    {
        $this->numeroRelance = $numeroRelance;

        return $this;
    }

    /**
     * Get numeroRelance
     *
     * @return int|null
     */
    public function getNumeroRelance(): ?int
    {
        return $this->numeroRelance;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ClientsStatusHistory
     */
    public function setAdded(\DateTime $added): ClientsStatusHistory
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->added->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }
}
