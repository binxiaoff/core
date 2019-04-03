<?php

namespace Unilend\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CompanyTaxFormType
 *
 * @ORM\Table(name="company_tax_form_type")
 * @ORM\Entity
 */
class CompanyTaxFormType
{
    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=100, unique=true)
     */
    private $label;

    /**
     * @var int
     *
     * @ORM\Column(name="id_type", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idType;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return CompanyTaxFormType
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
     * Get idType
     *
     * @return integer
     */
    public function getIdType()
    {
        return $this->idType;
    }
}
