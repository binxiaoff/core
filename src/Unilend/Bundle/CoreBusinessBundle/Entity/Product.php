<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * Product
 *
 * @ORM\Table(name="product")
 * @ORM\Entity(repositoryClass="Unilend\Bundle\CoreBusinessBundle\Repository\ProductRepository")
 */
class Product
{
    const STATUS_OFFLINE  = 0; // Unavailable in FO
    const STATUS_ONLINE   = 1; // available both in FO and BO
    const STATUS_ARCHIVED = 2; // unavailable either in FO or BO

    const PRODUCT_BLEND = 'amortization_ifp_blend_fr';

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false, unique=true)
     */
    private $label;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="smallint")
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="proxy_template", type="string", length=191)
     */
    private $proxyTemplate;

    /**
     * @var string
     *
     * @ORM\Column(name="proxy_block_slug", type="string", length=191)
     */
    private $proxyBlockSlug;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime")
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime")
     */
    private $updated;

    /**
     * @var int
     *
     * @ORM\Column(name="id_product", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProduct;

    /**
     * @var RepaymentType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\RepaymentType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_repayment_type", referencedColumnName="id_repayment_type", nullable=false)
     * })
     */
    private $idRepaymentType;

    /**
     * @var ProductUnderlyingContract[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProductUnderlyingContract", mappedBy="idProduct", fetch="EXTRA_LAZY")
     */
    private $productContract;

    /**
     * @var ProductAttribute[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttribute", mappedBy="idProduct")
     */
    private $productAttributes;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->productContract   = new ArrayCollection();
        $this->productAttributes = new ArrayCollection();
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return Product
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Product
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
     * Set proxyTemplate
     *
     * @param string $proxyTemplate
     *
     * @return Product
     */
    public function setProxyTemplate($proxyTemplate)
    {
        $this->proxyTemplate = $proxyTemplate;

        return $this;
    }

    /**
     * Get proxyTemplate
     *
     * @return string
     */
    public function getProxyTemplate()
    {
        return $this->proxyTemplate;
    }

    /**
     * Set proxyBlockSlug
     *
     * @param string $proxyBlockSlug
     *
     * @return Product
     */
    public function setProxyBlockSlug($proxyBlockSlug)
    {
        $this->proxyBlockSlug = $proxyBlockSlug;

        return $this;
    }

    /**
     * Get proxyBlockSlug
     *
     * @return string
     */
    public function getProxyBlockSlug()
    {
        return $this->proxyBlockSlug;
    }

    /**
     * Set added
     *
     * @param \DateTime $added
     *
     * @return Product
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
     * @return Product
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
     * Get idProduct
     *
     * @return integer
     */
    public function getIdProduct()
    {
        return $this->idProduct;
    }

    /**
     * Set idRepaymentType
     *
     * @param RepaymentType $idRepaymentType
     *
     * @return Product
     */
    public function setIdRepaymentType(RepaymentType $idRepaymentType = null)
    {
        $this->idRepaymentType = $idRepaymentType;

        return $this;
    }

    /**
     * Get idRepaymentType
     *
     * @return RepaymentType
     */
    public function getIdRepaymentType()
    {
        return $this->idRepaymentType;
    }

    /**
     * @return ProductUnderlyingContract[]
     */
    public function getProductContract(): iterable
    {
        return $this->productContract;
    }

    /**
     * @param ProductAttributeType|null $attributeType
     *
     * @return ProductAttribute[]|ArrayCollection
     */
    public function getProductAttributes(ProductAttributeType $attributeType = null)
    {
        if (null !== $attributeType && null !== $this->productAttributes) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('idType', $attributeType));

            return $this->productAttributes->matching($criteria);
        }

        return $this->productAttributes;
    }
}
