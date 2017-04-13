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
    const BORROWER_PROVISION                            = 'borrower_provision';
    const BORROWER_PROVISION_CANCEL                     = 'borrower_provision_cancel';
    const BORROWER_COMMISSION                           = 'borrower_commission';
    const BORROWER_WITHDRAW                             = 'borrower_withdraw';
    const CAPITAL_REPAYMENT                             = 'capital_repayment';
    const GROSS_INTEREST_REPAYMENT                      = 'gross_interest_repayment';
    const LENDER_LOAN                                   = 'lender_loan';
    const LENDER_PROVISION                              = 'lender_provision';
    const LENDER_PROVISION_CANCEL                       = 'lender_provision_cancel';
    const LENDER_WITHDRAW                               = 'lender_withdraw';
    const LENDER_TRANSFER                               = 'lender_transfer';
    const TAX_FR_ADDITIONAL_CONTRIBUTIONS               = 'tax_fr_contributions_additionnelles';
    const TAX_FR_ADDITIONAL_CONTRIBUTIONS_WITHDRAW      = 'tax_fr_contributions_additionnelles_withdraw';
    const TAX_FR_CRDS                                   = 'tax_fr_crds';
    const TAX_FR_CRDS_WITHDRAW                          = 'tax_fr_crds_withdraw';
    const TAX_FR_CSG                                    = 'tax_fr_csg';
    const TAX_FR_CSG_WITHDRAW                           = 'tax_fr_csg_withdraw';
    const TAX_FR_SOLIDARITY_DEDUCTIONS                  = 'tax_fr_prelevements_de_solidarite';
    const TAX_FR_SOLIDARITY_DEDUCTIONS_WITHDRAW         = 'tax_fr_prelevements_de_solidarite_withdraw';
    const TAX_FR_STATUTORY_CONTRIBUTIONS                = 'tax_fr_prelevements_obligatoires';
    const TAX_FR_STATUTORY_CONTRIBUTIONS_WITHDRAW       = 'tax_fr_prelevements_obligatoires_withdraw';
    const TAX_FR_SOCIAL_DEDUCTIONS                      = 'tax_fr_prelevements_sociaux';
    const TAX_FR_SOCIAL_DEDUCTIONS_WITHDRAW             = 'tax_fr_prelevements_sociaux_withdraw';
    const TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE          = 'tax_fr_retenues_a_la_source';
    const TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE_WITHDRAW = 'tax_fr_retenues_a_la_source_withdraw';
    const UNILEND_PROMOTIONAL_OPERATION                 = 'unilend_promotional_operation';
    const UNILEND_PROMOTIONAL_OPERATION_CANCEL          = 'unilend_promotional_operation_cancel';
    const UNILEND_PROMOTIONAL_OPERATION_PROVISION       = 'unilend_promotional_operation_provision';
    const UNILEND_PROVISION                             = 'unilend_provision';
    const UNILEND_WITHDRAW                              = 'unilend_withdraw';
    const UNILEND_LENDER_REGULARIZATION                 = 'unilend_lender_regularization';
    const COLLECTION_COMMISSION_PROVISION               = 'collection_commission_provision';
    const COLLECTION_COMMISSION_BORROWER                = 'collection_commission_borrower';
    const COLLECTION_COMMISSION_LENDER                  = 'collection_commission_lender';

    const TAX_TYPES_FR = [
        self::TAX_FR_STATUTORY_CONTRIBUTIONS,
        self::TAX_FR_CSG,
        self::TAX_FR_SOCIAL_DEDUCTIONS,
        self::TAX_FR_ADDITIONAL_CONTRIBUTIONS,
        self::TAX_FR_SOLIDARITY_DEDUCTIONS,
        self::TAX_FR_CRDS,
        self::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE
    ];

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
