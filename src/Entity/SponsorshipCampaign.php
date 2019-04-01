<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SponsorshipCampaign
 *
 * @ORM\Table(name="sponsorship_campaign")
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\SponsorshipCampaignRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class SponsorshipCampaign
{
    const STATUS_VALID    = 1;
    const STATUS_ARCHIVED = 2;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_sponsor", type="decimal", precision=12, scale=2)
     */
    private $amountSponsor;

    /**
     * @var string
     *
     * @ORM\Column(name="amount_sponsee", type="decimal", precision=12, scale=2)
     */
    private $amountSponsee;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start", type="date")
     */
    private $start;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end", type="date", nullable=true)
     */
    private $end;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var int
     *
     * @ORM\Column(name="max_number_sponsee", type="integer")
     */
    private $maxNumberSponsee;

    /**
     * @var int
     *
     * @ORM\Column(name="validity_days", type="smallint")
     */
    private $validityDays;

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
     * @return SponsorshipCampaign
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
     * @return SponsorshipCampaign
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
     * @return SponsorshipCampaign
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
     * @return SponsorshipCampaign
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
     * @param int $status
     *
     * @return SponsorshipCampaign
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
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
     * @return SponsorshipCampaign
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
     * Set validityDays
     *
     * @param integer $validityDays
     *
     * @return SponsorshipCampaign
     */
    public function setValidityDays($validityDays)
    {
        $this->validityDays = $validityDays;

        return $this;
    }

    /**
     * Get validityDays
     *
     * @return integer
     */
    public function getValidityDays()
    {
        return $this->validityDays;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return SponsorshipCampaign
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
     * @return SponsorshipCampaign
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
