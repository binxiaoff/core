<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Product
 *
 * @ORM\Table(name="product", uniqueConstraints={@ORM\UniqueConstraint(name="unq_product_label", columns={"label"})}, indexes={@ORM\Index(name="idx_product_id_repayment_type", columns={"id_repayment_type"})})
 * @ORM\Entity
 */
class Product
{
    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="proxy_template", type="string", length=191, nullable=false)
     */
    private $proxyTemplate;

    /**
     * @var string
     *
     * @ORM\Column(name="proxy_block_slug", type="string", length=191, nullable=false)
     */
    private $proxyBlockSlug;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added", type="datetime", nullable=false)
     */
    private $added;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated", type="datetime", nullable=false)
     */
    private $updated;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_product", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProduct;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\RepaymentType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\RepaymentType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_repayment_type", referencedColumnName="id_repayment_type")
     * })
     */
    private $idRepaymentType;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract", inversedBy="idProduct")
     * @ORM\JoinTable(name="product_underlying_contract",
     *   joinColumns={
     *     @ORM\JoinColumn(name="id_product", referencedColumnName="id_product")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="id_contract", referencedColumnName="id_contract")
     *   }
     * )
     */
    private $idContract;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->idContract = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @param boolean $status
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
     * @return boolean
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
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\RepaymentType $idRepaymentType
     *
     * @return Product
     */
    public function setIdRepaymentType(\Unilend\Bundle\CoreBusinessBundle\Entity\RepaymentType $idRepaymentType = null)
    {
        $this->idRepaymentType = $idRepaymentType;

        return $this;
    }

    /**
     * Get idRepaymentType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\RepaymentType
     */
    public function getIdRepaymentType()
    {
        return $this->idRepaymentType;
    }

    /**
     * Add idContract
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract $idContract
     *
     * @return Product
     */
    public function addIdContract(\Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract $idContract)
    {
        $this->idContract[] = $idContract;

        return $this;
    }

    /**
     * Remove idContract
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract $idContract
     */
    public function removeIdContract(\Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract $idContract)
    {
        $this->idContract->removeElement($idContract);
    }

    /**
     * Get idContract
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIdContract()
    {
        return $this->idContract;
    }
}
