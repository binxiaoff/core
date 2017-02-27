<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyBalanceType
 *
 * @ORM\Table(name="company_balance_type", indexes={@ORM\Index(name="code", columns={"code"}), @ORM\Index(name="idx_company_balance_type_company_tax_form_type", columns={"id_company_tax_form_type"})})
 * @ORM\Entity
 */
class CompanyBalanceType
{
    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=3, nullable=false)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="id_balance_type", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBalanceType;

    /**
     * @var \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyTaxFormType
     *
     * @ORM\ManyToOne(targetEntity="Unilend\Bundle\CoreBusinessBundle\Entity\CompanyTaxFormType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_company_tax_form_type", referencedColumnName="id_type")
     * })
     */
    private $idCompanyTaxFormType;



    /**
     * Set code
     *
     * @param string $code
     *
     * @return CompanyBalanceType
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return CompanyBalanceType
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
     * Get idBalanceType
     *
     * @return integer
     */
    public function getIdBalanceType()
    {
        return $this->idBalanceType;
    }

    /**
     * Set idCompanyTaxFormType
     *
     * @param \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyTaxFormType $idCompanyTaxFormType
     *
     * @return CompanyBalanceType
     */
    public function setIdCompanyTaxFormType(\Unilend\Bundle\CoreBusinessBundle\Entity\CompanyTaxFormType $idCompanyTaxFormType = null)
    {
        $this->idCompanyTaxFormType = $idCompanyTaxFormType;

        return $this;
    }

    /**
     * Get idCompanyTaxFormType
     *
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyTaxFormType
     */
    public function getIdCompanyTaxFormType()
    {
        return $this->idCompanyTaxFormType;
    }
}
