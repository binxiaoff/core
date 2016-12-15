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
    const BORROWER_PROJECT_COMMISSION               = 'borrower_project_commission';
    const BORROWER_PROVISION_BY_DIRECT_DEBIT        = 'borrower_provision_by_direct_debit';
    const BORROWER_PROVISION_BY_WIRE_TRANSFER       = 'borrower_provision_by_wire_transfer';
    const BORROWER_REPAYMENT_COMMISSION             = 'borrower_repayment_commission';
    const BORROWER_WITHDRAW_BY_WIRE_TRANSFER        = 'borrower_withdraw_by_wire_transfer';
    const CAPITAL_REPAYMENT                         = 'capital_repayment';
    const GROSS_INTEREST_REPAYMENT                  = 'gross_interest_repayment';
    const LENDER_BID                                = 'lender_bid';
    const LENDER_LOAN                               = 'lender_loan';
    const LENDER_PROVISION_BY_CREDIT_CARD           = 'lender_provision_by_credit_card';
    const LENDER_PROVISION_BY_WIRE_TRANSFER         = 'lender_provision_by_wire_transfer';
    const LENDER_REJECTED_BID                       = 'lender_rejected_bid';
    const LENDER_WITHDRAW_BY_WIRE_TRANSFER          = 'lender_withdraw_by_wire_transfer';
    const REJECTED_LOAN                             = 'rejected_loan';
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
    const UNILEND_PROMOTIONAL_OPERATION_WITHDRAW    = 'unilend_promotional_operation_withdraw';
    const UNILEND_PROVISION_BY_WIRE_TRANSFER        = 'unilend_provision_by_wire_transfer';
    const UNILEND_WITHDRAW_BY_WIRE_TRANSFER         = 'unilend_withdraw_by_wire_transfer';

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
