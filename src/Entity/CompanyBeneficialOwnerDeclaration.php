<?php

namespace Unilend\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyBeneficialOwnerDeclaration
 *
 * @ORM\Table(name="company_beneficial_owner_declaration", indexes={@ORM\Index(name="idx_beneficial_owner_declaration_id_company", columns={"id_company"})})
 * @ORM\Entity(repositoryClass="Unilend\Repository\CompanyBeneficialOwnerDeclarationRepository")
 * @ORM\HasLifecycleCallbacks
 */
class CompanyBeneficialOwnerDeclaration
{
    const STATUS_PENDING   = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_ARCHIVED  = 2;

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
     * @var \Unilend\Entity\Companies
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Companies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company", referencedColumnName="id_company", nullable=false)
     * })
     */
    private $idCompany;

    /**
     * @var BeneficialOwner[]|ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\BeneficialOwner", mappedBy="idDeclaration")
     */
    private $beneficialOwners;

    /**
     * Companies constructor.
     */
    public function __construct()
    {
        $this->beneficialOwners = new ArrayCollection();
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
     * @param \Unilend\Entity\Companies $idCompany
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

    /**
     * @return BeneficialOwner[]|ArrayCollection
     */
    public function getBeneficialOwners()
    {
        return $this->beneficialOwners;
    }
}
