<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Unilend\Bundle\CoreBusinessBundle\Entity\Traits\Lendable;

/**
 * Bids
 *
 * @ORM\Table(name="bids", indexes={@ORM\Index(name="idprojectstatus", columns={"id_project", "status"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\BidsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Bids
{
    use Lendable;

    const STATUS_PENDING                      = 0;
    const STATUS_ACCEPTED                     = 1;
    const STATUS_REJECTED                     = 2;
    const STATUS_TEMPORARILY_REJECTED_AUTOBID = 3;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Autobid|null
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Autobid")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_autobid", referencedColumnName="id_autobid")
     * })
     */
    private $idAutobid;

    /**
     * @var int|null
     *
     * @ORM\Column(name="ordre", type="integer", nullable=true)
     */
    private $ordre;

    /**
     * @var int
     *
     * @ORM\Column(name="id_bid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBid;

    /**
     * Set Autobid
     *
     * @param Autobid|null $autobid
     *
     * @return Bids
     */
    public function setAutobid(?Autobid $autobid): Bids
    {
        $this->idAutobid = $autobid;

        return $this;
    }

    /**
     * Get Autobid
     *
     * @return Autobid|null
     */
    public function getAutobid(): ?Autobid
    {
        return $this->idAutobid;
    }

    /**
     * Set ordre
     *
     * @param int|null $ordre
     *
     * @return Bids
     */
    public function setOrdre(?int $ordre): Bids
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre
     *
     * @return int|null
     */
    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    /**
     * Get idBid
     *
     * @return int
     */
    public function getIdBid(): int
    {
        return $this->idBid;
    }
}
