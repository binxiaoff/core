<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sponsorship
 *
 * @ORM\Table(name="sponsorship", uniqueConstraints={@ORM\UniqueConstraint(name="id_client_sponsee_id_client_sponsor", columns={"id_client_sponsee", "id_client_sponsor"})}, indexes={@ORM\Index(name="idx_sponsorship_status", columns={"status"}), @ORM\Index(name="idx_sponsorship_id_client_sponsor", columns={"id_client_sponsor"}), @ORM\Index(name="idx_sponsorship_id_client_sponsee", columns={"id_client_sponsee"}), @ORM\Index(name="idx_sponsorship_id_campaign", columns={"id_campaign"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\SponsorshipRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Sponsorship
{
    const STATUS_ONGOING         = 0;
    const STATUS_SPONSEE_PAID    = 1;
    const STATUS_SPONSOR_PAID    = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

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
     * @var \Unilend\Entity\SponsorshipCampaign
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\SponsorshipCampaign")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_campaign", referencedColumnName="id", nullable=false)
     * })
     */
    private $idCampaign;

    /**
     * @var \Unilend\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client_sponsor", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClientSponsor;

    /**
     * @var \Unilend\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client_sponsee", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClientSponsee;

    /**
     * Set status
     *
     * @param int $status
     *
     * @return Sponsorship
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Sponsorship
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
     * @return Sponsorship
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
     * Set idCampaign
     *
     * @param \Unilend\Entity\SponsorshipCampaign $idCampaign
     *
     * @return Sponsorship
     */
    public function setIdCampaign(\Unilend\Entity\SponsorshipCampaign $idCampaign = null)
    {
        $this->idCampaign = $idCampaign;

        return $this;
    }

    /**
     * Get idCampaign
     *
     * @return \Unilend\Entity\SponsorshipCampaign
     */
    public function getIdCampaign()
    {
        return $this->idCampaign;
    }

    /**
     * Set idClientSponsor
     *
     * @param \Unilend\Entity\Clients $idClientSponsor
     *
     * @return Sponsorship
     */
    public function setIdClientSponsor(\Unilend\Entity\Clients $idClientSponsor = null)
    {
        $this->idClientSponsor = $idClientSponsor;

        return $this;
    }

    /**
     * Get idClientSponsor
     *
     * @return \Unilend\Entity\Clients
     */
    public function getIdClientSponsor()
    {
        return $this->idClientSponsor;
    }

    /**
     * Set idClientSponsee
     *
     * @param \Unilend\Entity\Clients $idClientSponsee
     *
     * @return Sponsorship
     */
    public function setIdClientSponsee(\Unilend\Entity\Clients $idClientSponsee = null)
    {
        $this->idClientSponsee = $idClientSponsee;

        return $this;
    }

    /**
     * Get idClientSponsee
     *
     * @return \Unilend\Entity\Clients
     */
    public function getIdClientSponsee()
    {
        return $this->idClientSponsee;
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
