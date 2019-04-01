<?php

namespace Unilend\Entity;

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
     * @ORM\Column(name="commission_rate_funds", type="decimal", precision=4, scale=2)
     */
    private $commissionRateFunds;

    /**
     * @var string
     *
     * @ORM\Column(name="commission_rate_repayment", type="decimal", precision=4, scale=2)
     */
    private $commissionRateRepayment;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Unilend\Entity\Product
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_product", referencedColumnName="id_product", nullable=false)
     * })
     */
    private $idProduct;

    /**
     * @var \Unilend\Entity\Partner
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Entity\Partner", inversedBy="productAssociations")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_partner", referencedColumnName="id", nullable=false)
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
     * @param \Unilend\Entity\Product $idProduct
     *
     * @return PartnerProduct
     */
    public function setIdProduct(\Unilend\Entity\Product $idProduct = null)
    {
        $this->idProduct = $idProduct;

        return $this;
    }

    /**
     * Get idProduct
     *
     * @return \Unilend\Entity\Product
     */
    public function getIdProduct()
    {
        return $this->idProduct;
    }

    /**
     * Set idPartner
     *
     * @param \Unilend\Entity\Partner $idPartner
     *
     * @return PartnerProduct
     */
    public function setIdPartner(\Unilend\Entity\Partner $idPartner = null)
    {
        $this->idPartner = $idPartner;

        return $this;
    }

    /**
     * Get idPartner
     *
     * @return \Unilend\Entity\Partner
     */
    public function getIdPartner()
    {
        return $this->idPartner;
    }
}
