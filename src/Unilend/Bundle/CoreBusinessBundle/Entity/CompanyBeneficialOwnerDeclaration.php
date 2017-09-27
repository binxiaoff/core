<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyBeneficialOwnerDeclaration
 *
 * @ORM\Table(name="company_beneficial_owner_declaration", indexes={@ORM\Index(name="idx_beneficial_owner_declaration_id_company", columns={"id_company"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class CompanyBeneficialOwnerDeclaration
{
    const STATUS_PENDING   = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_ARCHIVED  = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=false)
     */
    private $status;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company")
     * })
     */
    private $idCompany;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\BeneficialOwner[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\BeneficialOwner", mappedBy="idDeclaration")
     */
    private $beneficialOwner;

    /**
     * Companies constructor.
     */
    public function __construct()
    {
        $this->beneficialOwner = new ArrayCollection();
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return CompanyBeneficialOwnerDeclaration
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return CompanyBeneficialOwnerDeclaration
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
     * @return CompanyBeneficialOwnerDeclaration
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
     * Set idCompany
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Companies $idCompany
     *
     * @return CompanyBeneficialOwnerDeclaration
     */
    public function setIdCompany(Companies $idCompany)
    {
        $this->idCompany = $idCompany;

        return $this;
    }

    /**
     * Get idCompany
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Companies
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

    /**
     * @return ArrayCollection|BeneficialOwner[]
     */
    public function getBeneficialOwner()
    {
        return $this->beneficialOwner;
    }
}
