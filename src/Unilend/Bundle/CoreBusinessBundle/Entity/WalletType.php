<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WalletType
 *
 * @ORM\Table(name="wallet_type", uniqueConstraints={@ORM\UniqueConstraint(name="label_UNIQUE", columns={"label"})})
 * @ORM\Entity
 */
class WalletType
{
    const BORROWER                             = 'borrower';
    const DEBT_COLLECTOR                       = 'debt_collector';
    const LENDER                               = 'lender';
    const PARTNER                              = 'partner';
    const TAX_FR_ADDITIONAL_CONTRIBUTIONS      = 'tax_fr_contributions_additionnelles';
    const TAX_FR_CRDS                          = 'tax_fr_crds';
    const TAX_FR_CSG                           = 'tax_fr_csg';
    const TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE = 'tax_fr_retenues_a_la_source';
    const TAX_FR_SOCIAL_DEDUCTIONS             = 'tax_fr_prelevements_sociaux';
    const TAX_FR_SOLIDARITY_DEDUCTIONS         = 'tax_fr_prelevements_de_solidarite';
    const TAX_FR_STATUTORY_CONTRIBUTIONS       = 'tax_fr_prelevements_obligatoires';
    const UNILEND                              = 'unilend';
    const UNILEND_PROMOTIONAL_OPERATION        = 'unilend_promotional_operation';

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;



    /**
     * Set label
     *
     * @param string $label
     *
     * @return WalletType
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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
