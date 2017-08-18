<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SponsorshipCampaigns
 *
 * @ORM\Table(name="sponsorship_campaigns")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity
 */
class SponsorshipCampaigns
{
    const STATUS_VALID    = 1;
    const STATUS_ARCHIVED = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_sponsor", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $amountSponsor;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_sponsee", type="decimal", precision=12, scale=2, nullable=false)
     */
    private $amountSponsee;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start", type="datetime", nullable=false)
     */
    private $start;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end", type="datetime", nullable=true)
     */
    private $end;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_number_sponsee", type="integer", nullable=false)
     */
    private $maxNumberSponsee;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set amountSponsor
     *
     * @param string $amountSponsor
     *
     * @return SponsorshipCampaigns
     */
    public function setAmountSponsor($amountSponsor)
    {
        $this->amountSponsor = $amountSponsor;

        return $this;
    }

    /**
     * Get amountSponsor
     *
     * @return string
     */
    public function getAmountSponsor()
    {
        return $this->amountSponsor;
    }

    /**
     * Set amountSponsee
     *
     * @param string $amountSponsee
     *
     * @return SponsorshipCampaigns
     */
    public function setAmountSponsee($amountSponsee)
    {
        $this->amountSponsee = $amountSponsee;

        return $this;
    }

    /**
     * Get amountSponsee
     *
     * @return string
     */
    public function getAmountSponsee()
    {
        return $this->amountSponsee;
    }

    /**
     * Set start
     *
     * @param \DateTime $start
     *
     * @return SponsorshipCampaigns
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set end
     *
     * @param \DateTime $end
     *
     * @return SponsorshipCampaigns
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get end
     *
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return SponsorshipCampaigns
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set maxNumberSponsee
     *
     * @param integer $maxNumberSponsee
     *
     * @return SponsorshipCampaigns
     */
    public function setMaxNumberSponsee($maxNumberSponsee)
    {
        $this->maxNumberSponsee = $maxNumberSponsee;

        return $this;
    }

    /**
     * Get maxNumberSponsee
     *
     * @return integer
     */
    public function getMaxNumberSponsee()
    {
        return $this->maxNumberSponsee;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return SponsorshipCampaigns
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
     * @return SponsorshipCampaigns
     */
    public function setUpdated($updated)
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
