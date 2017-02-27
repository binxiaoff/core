<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PartnerProduct
 *
 * @ORM\Table(name="partner_product", indexes={@ORM\Index(name="fk_partner_product_partner_id_partner", columns={"id_partner"}), @ORM\Index(name="fk_partner_product_product_id_product", columns={"id_product"})})
 * @ORM\Entity
 */
class PartnerProduct
{
    /**
     * @var string
     *
     * @ORM\Column(name="commission_rate_funds", type="decimal", precision=4, scale=2, nullable=false)
     */
    private $commissionRateFunds;

    /**
     * @var string
     *
     * @ORM\Column(name="commission_rate_repayment", type="decimal", precision=4, scale=2, nullable=false)
     */
    private $commissionRateRepayment;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

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
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\Partner
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\Partner")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_partner", referencedColumnName="id")
     * })
     */
    private $idPartner;



    /**
     * Set commissionRateFunds
     *
     * @param string $commissionRateFunds
     *
     * @return PartnerProduct
     */
    public function setCommissionRateFunds($commissionRateFunds)
    {
        $this->commissionRateFunds = $commissionRateFunds;

        return $this;
    }

    /**
     * Get commissionRateFunds
     *
     * @return string
     */
    public function getCommissionRateFunds()
    {
        return $this->commissionRateFunds;
    }

    /**
     * Set commissionRateRepayment
     *
     * @param string $commissionRateRepayment
     *
     * @return PartnerProduct
     */
    public function setCommissionRateRepayment($commissionRateRepayment)
    {
        $this->commissionRateRepayment = $commissionRateRepayment;

        return $this;
    }

    /**
     * Get commissionRateRepayment
     *
     * @return string
     */
    public function getCommissionRateRepayment()
    {
        return $this->commissionRateRepayment;
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
     * Set idProduct
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Product $idProduct
     *
     * @return PartnerProduct
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

    /**
     * Set idPartner
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\Partner $idPartner
     *
     * @return PartnerProduct
     */
    public function setIdPartner(\Unilend\Bundle\CoreBusinessBundle\Entity\Partner $idPartner = null)
    {
        $this->idPartner = $idPartner;

        return $this;
    }

    /**
     * Get idPartner
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Partner
     */
    public function getIdPartner()
    {
        return $this->idPartner;
    }
}
