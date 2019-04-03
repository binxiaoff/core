<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BeneficialOwner
 *
 * @ORM\Table(name="beneficial_owner", uniqueConstraints={@ORM\UniqueConstraint(name="id_client_id_declaration", columns={"id_client", "id_declaration"})})
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Unilend\Repository\BeneficialOwnerRepository")
 */
class BeneficialOwner
{
    /**
     * @var string
     *
     * @ORM\Column(name="percentage_detained", type="decimal", precision=5, scale=2, nullable=true)
     */
    private $percentageDetained;

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
     * @var \Unilend\Entity\CompanyBeneficialOwnerDeclaration
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\CompanyBeneficialOwnerDeclaration", inversedBy="beneficialOwners")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_declaration", referencedColumnName="id", nullable=false)
     * })
     */
    private $idDeclaration;

    /**
     * @var \Unilend\Entity\Clients
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Clients")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_client", referencedColumnName="id_client", nullable=false)
     * })
     */
    private $idClient;

    /**
     * @var \Unilend\Entity\BeneficialOwnerType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\BeneficialOwnerType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id")
     * })
     */
    private $idType;

    /**
     * Set percentageDetained
     *
     * @param string $percentageDetained
     *
     * @return BeneficialOwner
     */
    public function setPercentageDetained($percentageDetained)
    {
        $this->percentageDetained = $percentageDetained;

        return $this;
    }

    /**
     * Get percentageDetained
     *
     * @return string
     */
    public function getPercentageDetained()
    {
        return $this->percentageDetained;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return BeneficialOwner
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
     * @return BeneficialOwner
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
     * Set idDeclaration
     *
     * @param \Unilend\Entity\CompanyBeneficialOwnerDeclaration $idDeclaration
     *
     * @return BeneficialOwner
     */
    public function setIdDeclaration(CompanyBeneficialOwnerDeclaration $idDeclaration)
    {
        $this->idDeclaration = $idDeclaration;

        return $this;
    }

    /**
     * Get idDeclaration
     *
     * @return \Unilend\Entity\CompanyBeneficialOwnerDeclaration
     */
    public function getIdDeclaration()
    {
        return $this->idDeclaration;
    }

    /**
     * Set idClient
     *
     * @param \Unilend\Entity\Clients $idClient
     *
     * @return BeneficialOwner
     */
    public function setIdClient(Clients $idClient)
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
     * Set idType
     *
     * @param \Unilend\Entity\BeneficialOwnerType|null $idType
     *
     * @return BeneficialOwner
     */
    public function setIdType(BeneficialOwnerType $idType = null)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType
     *
     * @return \Unilend\Entity\BeneficialOwnerType|null
     */
    public function getIdType()
    {
        return $this->idType;
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
