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
    const LENDER                           = 'lender';
    const BORROWER                         = 'borrower';
    const UNILEND                          = 'unilend';
    const UNILEND_PROMOTIONAL_OPERATION    = 'unilend_promotional_operation';
    const TAX_PRELEVEMENTS_OBLIGATOIRES    = 'tax_prelevements_obligatoires';
    const TAX_RETENUES_A_LA_SOURCE         = 'tax_retenues_a_la_source';
    const TAX_CSG                          = 'tax_csg';
    const TAX_PRELEVEMENTS_SOCIAUX         = 'tax_prelevements_sociaux';
    const TAX_CONTRIBUTIONS_ADDITIONNELLES = 'tax_contributions_additionnelles';
    const TAX_PRELEVEMENTS_DE_SOLIDARITE   = 'tax_prelevements_de_solidarite';
    const TAX_CRDS                         = 'tax_crds';
    const DEBT_COLLECTOR                   = 'debt_collector';

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
