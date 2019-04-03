<?php

namespace Unilend\Entity;

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
     * @var \Unilend\Entity\Users
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Users")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id_user", nullable=false)
     * })
     */
    private $idUser;

    /**
     * @var \Unilend\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client")
     * })
     */
    private $idClient;

    /**
     * @var \Unilend\Entity\SponsorshipCampaign
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\SponsorshipCampaign")
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
     * @param \Unilend\Entity\Users $idUser
     *
     * @return SponsorshipBlacklist
     */
    public function setIdUser(\Unilend\Entity\Users $idUser = null)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return \Unilend\Entity\Users
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set idClient
     *
     * @param \Unilend\Entity\Clients $idClient
     *
     * @return SponsorshipBlacklist
     */
    public function setIdClient(\Unilend\Entity\Clients $idClient = null)
    {
        $this->idClient = $idClient;

        return $this;
    }

    /**
     * Get idClient
     *
     * @return \Unilend\Entity\Clients
     */
    public function getIdClient()
    {
        return $this->idClient;
    }

    /**
     * Set idCampaign
     *
     * @param \Unilend\Entity\SponsorshipCampaign $idCampaign
     *
     * @return SponsorshipBlacklist
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
     * @ORM\PrePersist
     */
    public function setAddedValue()
    {
        if (! $this->added instanceof \DateTime || 1 > $this->getAdded()->getTimestamp()) {
            $this->added = new \DateTime();
        }
    }
}
