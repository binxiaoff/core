<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Product
 *
 * @ORM\Table(name="product_underlying_contract")
 * @ORM\Entity
 */
class ProductUnderlyingContract
{
    /**
     * @var Product
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Product")
     * @ORM\JoinColumn(name="id_product", referencedColumnName="id_product", nullable=false)
     */
    private $idProduct;

    /**
     * @var UnderlyingContract
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract")
     * @ORM\JoinColumn(name="id_contract", referencedColumnName="id_contract", nullable=false)
     */
    private $idContract;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @return Product
     */
    public function getIdProduct(): Product
    {
        return $this->idProduct;
    }

    /**
     * @param Product $idProduct
     * @return ProductUnderlyingContract
     */
    public function setIdProduct(Product $idProduct): ProductUnderlyingContract
    {
        $this->idProduct = $idProduct;
        return $this;
    }

    /**
     * @return UnderlyingContract
     */
    public function getIdContract(): UnderlyingContract
    {
        return $this->idContract;
    }

    /**
     * @param UnderlyingContract $idContract
     * @return ProductUnderlyingContract
     */
    public function setIdContract(UnderlyingContract $idContract): ProductUnderlyingContract
    {
        $this->idContract = $idContract;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAdded(): \DateTime
    {
        return $this->added;
    }

    /**
     * @param \DateTime $added
     * @return ProductUnderlyingContract
     */
    public function setAdded(\DateTime $added): ProductUnderlyingContract
    {
        $this->added = $added;
        return $this;
    }
}
