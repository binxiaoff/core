<?php

namespace Unilend\Bundle\CoreBusinessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperationType
 *
 * @ORM\Table(name="operation_type", uniqueConstraints={@ORM\UniqueConstraint(name="label_UNIQUE", columns={"label"})})
 * @ORM\Entity
 */
class OperationType
{
    const BORROWER_PROVISION                        = 'borrower_provision';
    const BORROWER_PROVISION_CANCEL                 = 'borrower_provision_cancel';
    const BORROWER_COMMISSION                       = 'borrower_commission';
    const BORROWER_WITHDRAW                         = 'borrower_withdraw';
    const CAPITAL_REPAYMENT                         = 'capital_repayment';
    const GROSS_INTEREST_REPAYMENT                  = 'gross_interest_repayment';
    const LENDER_LOAN                               = 'lender_loan';
    const LENDER_PROVISION                          = 'lender_provision';
    const LENDER_PROVISION_CANCEL                   = 'lender_provision_cancel';
    const LENDER_WITHDRAW                           = 'lender_withdraw';
    const LENDER_TRANSFER                           = 'lender_transfer';
    const TAX_CONTRIBUTIONS_ADDITIONNELLES          = 'tax_contributions_additionnelles';
    const TAX_CONTRIBUTIONS_ADDITIONNELLES_WITHDRAW = 'tax_contributions_additionnelles_withdraw';
    const TAX_CRDS                                  = 'tax_crds';
    const TAX_CRDS_WITHDRAW                         = 'tax_crds_withdraw';
    const TAX_CSG                                   = 'tax_csg';
    const TAX_CSG_WITHDRAW                          = 'tax_csg_withdraw';
    const TAX_PRELEVEMENTS_DE_SOLIDARITE            = 'tax_prelevements_de_solidarite';
    const TAX_PRELEVEMENTS_DE_SOLIDARITE_WITHDRAW   = 'tax_prelevements_de_solidarite_withdraw';
    const TAX_PRELEVEMENTS_OBLIGATOIRES             = 'tax_prelevements_obligatoires';
    const TAX_PRELEVEMENTS_OBLIGATOIRES_WITHDRAW    = 'tax_prelevements_obligatoires_withdraw';
    const TAX_PRELEVEMENTS_SOCIAUX                  = 'tax_prelevements_sociaux';
    const TAX_PRELEVEMENTS_SOCIAUX_WITHDRAW         = 'tax_prelevements_sociaux_withdraw';
    const TAX_RETENUES_A_LA_SOURCE                  = 'tax_retenues_a_la_source';
    const TAX_RETENUES_A_LA_SOURCE_WITHDRAW         = 'tax_retenues_a_la_source_withdraw';
    const UNILEND_PROMOTIONAL_OPERATION             = 'unilend_promotional_operation';
    const UNILEND_PROMOTIONAL_OPERATION_CANCEL      = 'unilend_promotional_operation_cancel';
    const UNILEND_PROMOTIONAL_OPERATION_PROVISION   = 'unilend_promotional_operation_provision';
    const UNILEND_PROVISION                         = 'unilend_provision';
    const UNILEND_WITHDRAW                          = 'unilend_withdraw';
    const UNILEND_LENDER_REGULARIZATION             = 'unilend_lender_regularization';
    const COLLECTION_COMMISSION_PROVISION           = 'collection_commission_provision';
    const COLLECTION_COMMISSION_BORROWER            = 'collection_commission_borrower';
    const COLLECTION_COMMISSION_LENDER              = 'collection_commission_lender';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=191, nullable=false)
     */
    private $label;


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
     * Set label
     *
     * @param string $label
     *
     * @return OperationType
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
}
