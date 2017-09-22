<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SponsorshipBlacklist
 *
 * @ORM\Table(name="sponsorship_blacklist", indexes={@ORM\Index(name="idx_sponsorship_blacklist_id_client", columns={"id_client"}), @ORM\Index(name="idx_sponsorship_blacklist_id_user", columns={"id_user"})})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\SponsorshipBlacklistRepository")
 */
class SponsorshipBlacklist
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id_user")
     * })
     */
    private $idUser;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client")
     * })
     */
    private $idClient;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_campaign", referencedColumnName="id")
     * })
     */
    private $idCampaign;


    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return SponsorshipBlacklist
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idUser
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Users $idUser
     *
     * @return SponsorshipBlacklist
     */
    public function setIdUser(\Unilend\Bundle\CoreBusinessBundle\Entity\Users $idUser = null)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Users
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set idClient
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Clients $idClient
     *
     * @return SponsorshipBlacklist
     */
    public function setIdClient(\Unilend\Bundle\CoreBusinessBundle\Entity\Clients $idClient = null)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Clients
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set idCampaign
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign $idCampaign
     *
     * @return SponsorshipBlacklist
     */
    public function setIdCampaign(\Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign $idCampaign = null)
    {
        $this->idCampaign = $idCampaign;

        return $this;
    }

    /**
     * Get idCampaign
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign
     */
    public function getIdCampaign()
    {
        return $this->idCampaign;
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
