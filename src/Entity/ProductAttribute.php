<?php

namespace Unilend\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProductAttribute.
 *
 * @ORM\Table(
 *     name="product_attribute",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="unq_attribute_product_type_value", columns={"id_product", "id_type", "attribute_value"})},
 *     indexes={@ORM\Index(name="idx_attribute_id_product", columns={"id_product"}), @ORM\Index(name="idx_product_attribute_id_type", columns={"id_type"})}
 * )
 * @ORM\Entity
 */
class ProductAttribute
{
    /**
     * @var string
     *
     * @ORM\Column(name="attribute_value", type="string", length=191)
     */
    private $attributeValue;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="update", type="datetime")
     */
    private $update;

    /**
     * @var int
     *
     * @ORM\Column(name="id_attribute", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idAttribute;

    /**
     * @var ProductAttributeType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProductAttributeType")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_type", referencedColumnName="id_type", nullable=false)
     * })
     */
    private $idType;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Product", inversedBy="productAttributes")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_product", referencedColumnName="id_product", nullable=false)
     * })
     */
    private $idProduct;

    /**
     * @var ProjectEligibilityRule
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\ProjectEligibilityRule")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_rule", referencedColumnName="id")
     * })
     */
    private $idRule;

    /**
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
     * @return string
     */
    public function getAttributeValue()
    {
        return $this->attributeValue;
    }

    /**
     * @param DateTime $added
     *
     * @return ProductAttribute
     */
    public function setAdded($added)
    {
        $this->added = $added;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @param DateTime $update
     *
     * @return ProductAttribute
     */
    public function setUpdate($update)
    {
        $this->update = $update;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdate()
    {
        return $this->update;
    }

    /**
     * @return int
     */
    public function getIdAttribute()
    {
        return $this->idAttribute;
    }

    /**
     * @param ProductAttributeType $idType
     *
     * @return ProductAttribute
     */
    public function setIdType(ProductAttributeType $idType = null)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * @return ProductAttributeType
     */
    public function getIdType()
    {
        return $this->idType;
    }

    /**
     * @param Product $idProduct
     *
     * @return ProductAttribute
     */
    public function setIdProduct(Product $idProduct = null)
    {
        $this->idProduct = $idProduct;

        return $this;
    }

    /**
     * @return Product
     */
    public function getIdProduct()
    {
        return $this->idProduct;
    }

    /**
     * @return ProjectEligibilityRule
     */
    public function getIdRule()
    {
        return $this->idRule;
    }

    /**
     * @param ProjectEligibilityRule $idRule
     *
     * @return ProductAttribute
     */
    public function setIdRule(ProjectEligibilityRule $idRule)
    {
        $this->idRule = $idRule;

        return $this;
    }
}
