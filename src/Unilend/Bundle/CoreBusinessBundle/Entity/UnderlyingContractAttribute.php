<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UnderlyingContractAttribute
 *
 * @ORM\Table(name="underlying_contract_attribute", uniqueConstraints={@ORM\UniqueConstraint(name="unq_attribute_contract_type_value", columns={"id_contract", "id_type", "attribute_value"})}, indexes={@ORM\Index(name="idx_underlying_contract_attribute_id_contract", columns={"id_contract"}), @ORM\Index(name="idx_underlying_contract_attribute_id_type", columns={"id_type"})})
 * @ORM\Entity
 */
class UnderlyingContractAttribute
{
    /**
     * @var string
     *
     * @ORM\Column(name="attribute_value", type="string", length=191, nullable=false)
     */
    private $attributeValue;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_attribute", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAttribute;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id_type")
     * })
     */
    private $idType;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_contract", referencedColumnName="id_contract")
     * })
     */
    private $idContract;



    /**
     * Set attributeValue
     *
     * @param string $attributeValue
     *
     * @return UnderlyingContractAttribute
     */
    public function setAttributeValue($attributeValue)
    {
        $this->attributeValue = $attributeValue;

        return $this;
    }

    /**
     * Get attributeValue
     *
     * @return string
     */
    public function getAttributeValue()
    {
        return $this->attributeValue;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return UnderlyingContractAttribute
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return UnderlyingContractAttribute
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
     * Get idAttribute
     *
     * @return integer
     */
    public function getIdAttribute()
    {
        return $this->idAttribute;
    }

    /**
     * Set idType
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType $idType
     *
     * @return UnderlyingContractAttribute
     */
    public function setIdType(\Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType $idType = null)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType
     */
    public function getIdType()
    {
        return $this->idType;
    }

    /**
     * Set idContract
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract $idContract
     *
     * @return UnderlyingContractAttribute
     */
    public function setIdContract(\Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract $idContract = null)
    {
        $this->idContract = $idContract;

        return $this;
    }

    /**
     * Get idContract
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract
     */
    public function getIdContract()
    {
        return $this->idContract;
    }
}
