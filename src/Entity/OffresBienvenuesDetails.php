<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OffresBienvenuesDetails
 *
 * @ORM\Table(name="offres_bienvenues_details", indexes={@ORM\Index(name="id_client", columns={"id_client"}), @ORM\Index(name="id_offre_bienvenue", columns={"id_offre_bienvenue"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\OffresBienvenuesDetailsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class OffresBienvenuesDetails
{
    const STATUS_NEW      = 0;
    const STATUS_USED     = 1;
    const STATUS_CANCELED = 2;

    const TYPE_OFFER   = 0;
    const TYPE_CUT     = 1;
    const TYPE_PAYBACK = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="id_offre_bienvenue", type="integer")
     */
    private $idOffreBienvenue;

    /**
     * @var int
     *
     * @ORM\Column(name="id_client", type="integer")
     */
    private $idClient;

    /**
     * @var int
     *
     * @ORM\Column(name="id_bid", type="integer", nullable=true)
     */
    private $idBid;

    /**
     * @var int
     *
     * @ORM\Column(name="id_bid_remb", type="integer", nullable=true)
     */
    private $idBidRemb;

    /**
     * @var int
     *
     * @ORM\Column(name="montant", type="integer")
     */
    private $montant;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="smallint")
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_offre_bienvenue_detail", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idOffreBienvenueDetail;



    /**
     * Set idOffreBienvenue
     *
     * @param integer $idOffreBienvenue
     *
     * @return OffresBienvenuesDetails
     */
    public function setIdOffreBienvenue($idOffreBienvenue)
    {
        $this->idOffreBienvenue = $idOffreBienvenue;

        return $this;
    }

    /**
     * Get idOffreBienvenue
     *
     * @return integer
     */
    public function getIdOffreBienvenue()
    {
        return $this->idOffreBienvenue;
    }

    /**
     * Set idClient
     *
     * @param integer $idClient
     *
     * @return OffresBienvenuesDetails
     */
    public function setIdClient($idClient)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return integer
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set idBid
     *
     * @param integer $idBid
     *
     * @return OffresBienvenuesDetails
     */
    public function setIdBid($idBid = null)
    {
        $this->idBid = $idBid;

        return $this;
    }

    /**
     * Get idBid
     *
     * @return integer|null
     */
    public function getIdBid()
    {
        return $this->idBid;
    }

    /**
     * Set idBidRemb
     *
     * @param integer $idBidRemb
     *
     * @return OffresBienvenuesDetails
     */
    public function setIdBidRemb($idBidRemb = null)
    {
        $this->idBidRemb = $idBidRemb;

        return $this;
    }

    /**
     * Get idBidRemb
     *
     * @return integer|null
     */
    public function getIdBidRemb()
    {
        return $this->idBidRemb;
    }

    /**
     * Set montant
     *
     * @param integer $montant
     *
     * @return OffresBienvenuesDetails
     */
    public function setMontant($montant)
    {
        $this->montant = $montant;

        return $this;
    }

    /**
     * Get montant
     *
     * @return integer
     */
    public function getMontant()
    {
        return $this->montant;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return OffresBienvenuesDetails
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set type
     *
     * @param integer $type
     *
     * @return OffresBienvenuesDetails
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return OffresBienvenuesDetails
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * Get added
     *
     * @return \DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return OffresBienvenuesDetails
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Get idOffreBienvenueDetail
     *
     * @return integer
     */
    public function getIdOffreBienvenueDetail()
    {
        return $this->idOffreBienvenueDetail;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue()
    {
        $this->updated = new \DateTime();
    }
}
