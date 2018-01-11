<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AcceptedBids
 *
 * @ORM\Table(name="accepted_bids", uniqueConstraints={@ORM\UniqueConstraint(name="unq_accepted_bids_id_bid_id_loan", columns={"id_bid", "id_loan"})}, indexes={@ORM\Index(name="idx_accepted_bids_id_loan", columns={"id_loan"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\AcceptedBidsRepository")
 */
class AcceptedBids
{
    /**
     * @var Bids
     *
     * @ORM\Column(name="id_bid", type="integer", nullable=false)
     */
    private $idBid;

    /**
     * @var Loans
     *
     * @ORM\Column(name="id_loan", type="integer", nullable=true)
     */
    private $idLoan;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="integer", nullable=false)
     */
    private $amount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_accepted_bid", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAcceptedBid;



    /**
     * Set idBid
     *
     * @param Bids $idBid
     *
     * @return AcceptedBids
     */
    public function setIdBid($idBid)
    {
        $this->idBid = $idBid;

        return $this;
    }

    /**
     * Get idBid
     *
     * @return Bids
     */
    public function getIdBid()
    {
        return $this->idBid;
    }

    /**
     * Set idLoan
     *
     * @param Loans $idLoan
     *
     * @return AcceptedBids
     */
    public function setIdLoan($idLoan)
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get idLoan
     *
     * @return Loans
     */
    public function getIdLoan()
    {
        return $this->idLoan;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return AcceptedBids
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Get idAcceptedBid
     *
     * @return integer
     */
    public function getIdAcceptedBid()
    {
        return $this->idAcceptedBid;
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
