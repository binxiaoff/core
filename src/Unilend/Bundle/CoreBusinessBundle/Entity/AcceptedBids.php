<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AcceptedBids
 *
 * @ORM\Table(name="accepted_bids", uniqueConstraints={@ORM\UniqueConstraint(name="unq_accepted_bids_id_bid_id_loan", columns={"id_bid", "id_loan"})}, indexes={@ORM\Index(name="idx_accepted_bids_id_loan", columns={"id_loan"}), @ORM\Index(name="idx_accepted_bids_id_bid", columns={"id_bid"})})
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\AcceptedBidsRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AcceptedBids
{
    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Bids
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Bids")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_bid", referencedColumnName="id_bid", nullable=false)
     * })
     */
    private $idBid;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Loans
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Loans")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_loan", referencedColumnName="id_loan")
     * })
     */
    private $idLoan;

    /**
     * @var int
     *
     * @ORM\Column(name="amount", type="integer")
     */
    private $amount;

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
    public function setIdBid(Bids $idBid) : AcceptedBids
    {
        $this->idBid = $idBid;

        return $this;
    }

    /**
     * Get idBid
     *
     * @return Bids
     */
    public function getIdBid() : Bids
    {
        return $this->idBid;
    }

    /**
     * Set idLoan
     *
     * @param Loans|null $idLoan
     *
     * @return AcceptedBids
     */
    public function setIdLoan(?Loans $idLoan) : AcceptedBids
    {
        $this->idLoan = $idLoan;

        return $this;
    }

    /**
     * Get idLoan
     *
     * @return Loans
     */
    public function getIdLoan() : Loans
    {
        return $this->idLoan;
    }

    /**
     * Set amount
     *
     * @param int $amount
     *
     * @return AcceptedBids
     */
    public function setAmount(int $amount) : AcceptedBids
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return int
     */
    public function getAmount() : int
    {
        return $this->amount;
    }

    /**
     * Get idAcceptedBid
     *
     * @return int
     */
    public function getIdAcceptedBid() : int
    {
        return $this->idAcceptedBid;
    }

    /**
     * @ORM\PrePersist
     */
    public function setAddedValue() : void
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function setUpdatedValue() : void
    {
        $this->updated = new \DateTime();
    }

    /**
     * @return \DateTime
     */
    public function getAdded() : \DateTime
    {
        return $this->added;
    }

    /**
     * @param \DateTime $added
     *
     * @return AcceptedBids
     */
    public function setAdded(\DateTime $added) : AcceptedBids
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated() : \DateTime
    {
        return $this->updated;
    }

    /**
     * @param \DateTime|null $updated
     *
     * @return AcceptedBids
     */
    public function setUpdated(?\DateTime $updated) : AcceptedBids
    {
        $this->updated = $updated;

        return $this;
    }
}
