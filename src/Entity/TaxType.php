<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TaxType
 *
 * @ORM\Table(name="tax_type")
 * @ORM\Entity
 */
class TaxType
{
    const TYPE_VAT                                          = 1;
    const TYPE_STATUTORY_CONTRIBUTIONS                      = 2;
    const TYPE_CSG                                          = 3;
    const TYPE_SOCIAL_DEDUCTIONS                            = 4;
    const TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS = 5;
    const TYPE_SOLIDARITY_DEDUCTIONS                        = 6;
    const TYPE_CRDS                                         = 7;
    const TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE                = 8;
    const TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE_PERSON         = 9;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=191)
     */
    private $name;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="decimal", precision=5, scale=2)
     */
    private $rate;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=2)
     */
    private $country;

    /**
     * @var int
     *
     * @ORM\Column(name="id_tax_type", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTaxType;

    /**
     * Set name
     *
     * @param string $name
     *
     * @return TaxType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set rate
     *
     * @param float $rate
     *
     * @return TaxType
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set country
     *
     * @param string $country
     *
     * @return TaxType
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Get idTaxType
     *
     * @return integer
     */
    public function getIdTaxType()
    {
        return $this->idTaxType;
    }
}
