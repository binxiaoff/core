<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PartnerThirdParty
 *
 * @ORM\Table(name="partner_third_party", uniqueConstraints={@ORM\UniqueConstraint(name="unq_partner_third_party_company_partner", columns={"id_company", "id_partner"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class PartnerThirdParty
{
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
     * @var \Unilend\Entity\PartnerThirdPartyType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\PartnerThirdPartyType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id", nullable=false)
     * })
     */
    private $idType;

    /**
     * @var \Unilend\Entity\Partner
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Partner", inversedBy="partnerThirdParties")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_partner", referencedColumnName="id", nullable=false)
     * })
     */
    private $idPartner;

    /**
     * @var \Unilend\Entity\Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company", nullable=false)
     * })
     */
    private $idCompany;


    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return PartnerThirdParty
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
     * @return PartnerThirdParty
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
     * Set idType
     *
     * @param \Unilend\Entity\PartnerThirdPartyType $idType
     *
     * @return PartnerThirdParty
     */
    public function setIdType(\Unilend\Entity\PartnerThirdPartyType $idType = null)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType
     *
     * @return \Unilend\Entity\PartnerThirdPartyType
     */
    public function getIdType()
    {
        return $this->idType;
    }

    /**
     * Set idPartner
     *
     * @param \Unilend\Entity\Partner $idPartner
     *
     * @return PartnerThirdParty
     */
    public function setIdPartner(\Unilend\Entity\Partner $idPartner = null)
    {
        $this->idPartner = $idPartner;

        return $this;
    }

    /**
     * Get idPartner
     *
     * @return \Unilend\Entity\Partner
     */
    public function getIdPartner()
    {
        return $this->idPartner;
    }

    /**
     * Set idCompany
     *
     * @param \Unilend\Entity\Companies $idCompany
     *
     * @return PartnerThirdParty
     */
    public function setIdCompany(\Unilend\Entity\Companies $idCompany = null)
    {
        $this->idCompany = $idCompany;

        return $this;
    }

    /**
     * Get idCompany
     *
     * @return \Unilend\Entity\Companies
     */
    public function getIdCompany()
    {
        return $this->idCompany;
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
