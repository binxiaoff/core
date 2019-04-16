<?php

declare(strict_types=1);

namespace Unilend\Entity;

use Doctrine\Common\Collections\{ArrayCollection, Criteria};
use Doctrine\ORM\Mapping as ORM;
use Unilend\Entity\Traits\Timestampable;

/**
 * @ORM\Table(name="product")
 * @ORM\Entity(repositoryClass="Unilend\Repository\ProductRepository")
 */
class Product
{
    use Timestampable;

    public const STATUS_OFFLINE  = 0; // Unavailable in FO
    public const STATUS_ONLINE   = 1; // available both in FO and BO
    public const STATUS_ARCHIVED = 2; // unavailable either in FO or BO

    public const PRODUCT_BLEND = 'amortization_ifp_blend_fr';

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
     * @ORM\Column(name="proxy_template", type="string", length=191, nullable=true)
     */
    private $proxyTemplate;

    /**
     * @var string
     *
     * @ORM\Column(name="proxy_block_slug", type="string", length=191, nullable=true)
     */
    private $proxyBlockSlug;

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
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\RepaymentType")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="id_repayment_type", referencedColumnName="id_repayment_type", nullable=false)
     * })
     */
    private $idRepaymentType;

    /**
     * @var ProductUnderlyingContract[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProductUnderlyingContract", mappedBy="idProduct", fetch="EXTRA_LAZY")
     */
    private $productContract;

    /**
     * @var ProductAttribute[]
     *
     * @ORM\OneToMany(targetEntity="Unilend\Entity\ProductAttribute", mappedBy="idProduct")
     */
    private $productAttributes;

    /**
     * Product constructor.
     */
    public function __construct()
    {
        $this->productContract   = new ArrayCollection();
        $this->productAttributes = new ArrayCollection();
    }

    /**
     * @param string $label
     *
     * @return Product
     */
    public function setLabel(string $label): Product
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param int $status
     *
     * @return Product
     */
    public function setStatus(int $status): Product
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param string $proxyTemplate
     *
     * @return Product
     */
    public function setProxyTemplate(string $proxyTemplate): Product
    {
        $this->proxyTemplate = $proxyTemplate;

        return $this;
    }

    /**
     * @return string
     */
    public function getProxyTemplate(): string
    {
        return $this->proxyTemplate;
    }

    /**
     * @param string $proxyBlockSlug
     *
     * @return Product
     */
    public function setProxyBlockSlug(string $proxyBlockSlug): Product
    {
        $this->proxyBlockSlug = $proxyBlockSlug;

        return $this;
    }

    /**
     * @return string
     */
    public function getProxyBlockSlug(): string
    {
        return $this->proxyBlockSlug;
    }

    /**
     * @return int
     */
    public function getIdProduct(): int
    {
        return $this->idProduct;
    }

    /**
     * @param RepaymentType $idRepaymentType
     *
     * @return Product
     */
    public function setIdRepaymentType(RepaymentType $idRepaymentType = null): Product
    {
        $this->idRepaymentType = $idRepaymentType;

        return $this;
    }

    /**
     * @return RepaymentType
     */
    public function getIdRepaymentType(): RepaymentType
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
    public function getProductAttributes(ProductAttributeType $attributeType = null): iterable
    {
        if (null !== $attributeType && null !== $this->productAttributes) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()->eq('idType', $attributeType))
            ;

            return $this->productAttributes->matching($criteria);
        }

        return $this->productAttributes;
    }
}
