<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ClientsHistory
 *
 * @ORM\Table(name="clients_history", indexes={@ORM\Index(name="id_client", columns={"id_client"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ClientsHistory
{
    const TYPE_CLIENT_LENDER          = 1;
    const TYPE_CLIENT_BORROWER        = 2;
    const TYPE_CLIENT_LENDER_BORROWER = 3;
    const TYPE_CLIENT_PARTNER         = 4;

    const STATUS_ACTION_LOGIN            = 1;
    const STATUS_ACTION_ACCOUNT_CREATION = 2;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_history", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idHistory;



    /**
     * Set idClient
     *
     * @param Clients $idClient
     *
     * @return ClientsHistory
     */
    public function setIdClient(Clients $idClient): ClientsHistory
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
     * Set type
     *
     * @param int $type
     *
     * @return ClientsHistory
     */
    public function setType(int $type): ClientsHistory
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Set status
     *
     * @param int $status
     *
     * @return ClientsHistory
     */
    public function setStatus(int $status): ClientsHistory
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ClientsHistory
     */
    public function setAdded(\DateTime $added): ClientsHistory
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
     * Get idHistory
     *
     * @return int
     */
    public function getIdHistory(): int
    {
        return $this->idHistory;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue(): void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }
}
