<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductAttribute
 *
 * @ORM\Table(name="product_attribute", uniqueConstraints={@ORM\UniqueConstraint(name="unq_attribute_product_type_value", columns={"id_product", "id_type", "attribute_value"})}, indexes={@ORM\Index(name="idx_attribute_id_product", columns={"id_product"}), @ORM\Index(name="idx_product_attribute_id_type", columns={"id_type"})})
 * @ORM\Entity
 */
class ProductAttribute
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
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update", type="datetime", nullable=false)
     */
    private $update;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_attribute", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAttribute;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_type", referencedColumnName="id_type")
     * })
     */
    private $idType;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Product
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_product", referencedColumnName="id_product")
     * })
     */
    private $idProduct;



    /**
     * Set attributeValue
     *
     * @param string $attributeValue
     *
     * @return ProductAttribute
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
     * Set added
     *
     * @param \DateTime $added
     *
     * @return ProductAttribute
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
     * Set update
     *
     * @param \DateTime $update
     *
     * @return ProductAttribute
     */
    public function setUpdate($update)
    {
        $this->update = $update;

        return $this;
    }

    /**
     * Get update
     *
     * @return \DateTime
     */
    public function getUpdate()
    {
        return $this->update;
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
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType $idType
     *
     * @return ProductAttribute
     */
    public function setIdType(\Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType $idType = null)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType
     */
    public function getIdType()
    {
        return $this->idType;
    }

    /**
     * Set idProduct
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Product $idProduct
     *
     * @return ProductAttribute
     */
    public function setIdProduct(\Unilend\Bundle\CoreBusinessBundle\Entity\Product $idProduct = null)
    {
        $this->idProduct = $idProduct;

        return $this;
    }

    /**
     * Get idProduct
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Product
     */
    public function getIdProduct()
    {
        return $this->idProduct;
    }
}
