<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class TaxManager
{
    /** @var  EntityManager */
    private $entityManager;

    /**
     * TaxManager constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param \transactions $transaction
     * @throws \Exception
     * @return int the total tax amount applied on the transaction
     */
    public function taxTransaction(\transactions $transaction)
    {
        switch ($transaction->type_transaction) {
            case \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS:
                return $this->taxLenderRepaymentInterests($transaction);
                break;
            case \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL:
                return 0;
                break;
        }
    }

    /**
     * @param \transactions $transaction
     * @param array $taxesTypes
     * @return int
     */
    private function applyTaxes(\transactions &$transaction, array $taxesTypes)
    {
        /** @var \tax $tax */
        $tax = $this->entityManager->getRepository('tax');
        /** @var \tax_type $taxType */
        $taxType = $this->entityManager->getRepository('tax_type');

        $taxes           = array();
        $iTotalTaxAmount = 0;

        foreach ($taxesTypes as $taxTypeId) {
            $taxType->get($taxTypeId);
            $iTaxAmount = round($transaction->montant * bcdiv($taxType->rate, 100, 2));
            $iTotalTaxAmount += $iTaxAmount;

            $taxes[] = array(
                'id_tax_type'    => $taxTypeId,
                'id_transaction' => $transaction->id_transaction,
                'amount'         => $iTaxAmount,
                'added'          => date('Y-m-d H:i:s'),
                'updated'        => date('Y-m-d H:i:s'),
            );
        }
        $transaction->montant -= $iTaxAmount;
        $transaction->update();
        $tax->multiInsert($taxes);

        return $iTotalTaxAmount;
    }

    /**
     * @param \transactions $transaction
     * @throws \Exception
     * @return int
     */
    private function taxLenderRepaymentInterests(\transactions $transaction)
    {
        /** @var \clients $client */
        $client = $this->entityManager->getRepository('clients');

        if (false === $client->get($transaction->id_client)) {
            throw new \Exception('Unable to load client ' . $transaction->id_client);
        }

        switch ($client->type) {
            case \clients::TYPE_LEGAL_ENTITY:
            case \clients::TYPE_LEGAL_ENTITY_FOREIGNER:
                return $this->taxLegalEntityLenderRepaymentInterests($transaction);
            case \clients::TYPE_PERSON:
            case \clients::TYPE_PERSON_FOREIGNER:
            default:
                return $this->taxNaturalPersonLenderRepaymentInterests($transaction);
        }
    }

    /**
     * @param \transactions $transaction $transactions
     * @throws \Exception
     * @return int
     */
    private function taxNaturalPersonLenderRepaymentInterests(\transactions $transaction)
    {
        /** @var \lenders_accounts $lender */
        $lender = $this->entityManager->getRepository('lenders_accounts');

        if (false === $lender->get($transaction->id_client, 'id_client_owner')) {
            throw new \Exception('Unable to load lender with client ID ' . $transaction->id_client);
        }

        /** @var \lenders_imposition_history $lenderTaxationHistory */
        $lenderTaxationHistory = $this->entityManager->getRepository('lenders_imposition_history');

        if (false === $lenderTaxationHistory->loadLastTaxationHistory($lender->id_lender_account)) {
            /**
             * throw new \Exception('Unable to load lender taxation history with lender ID ' . $lender->id_lender_account);
             */
            /** @todo this is a temporary fix, uncomment the line above and remove this line once the TMA-761 was done */
            $lenderTaxationHistory->resident_etranger = 0;
        }

        if (0 == $lenderTaxationHistory->resident_etranger) {
            /** @var \lender_tax_exemption $taxExemption */
            $taxExemption = $this->entityManager->getRepository('lender_tax_exemption');

            if ($taxExemption->get($lender->id_lender_account . '" AND year = "' . substr($transaction->added, 0, 4) . '" AND iso_country = "FR', 'id_lender')) { // @todo i18n
                return $this->applyTaxes($transaction, array(\tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS, \tax_type::TYPE_CRDS, \tax_type::TYPE_CSG, \tax_type::TYPE_SOLIDARITY_DEDUCTIONS, \tax_type::TYPE_SOCIAL_DEDUCTIONS));
            } else {
                return $this->applyTaxes($transaction, array(\tax_type::TYPE_INCOME_TAX, \tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS, \tax_type::TYPE_CRDS, \tax_type::TYPE_CSG, \tax_type::TYPE_SOLIDARITY_DEDUCTIONS, \tax_type::TYPE_SOCIAL_DEDUCTIONS));
            }
        } else {
            /** @var \echeanciers $repayment */
            $repayment = $this->entityManager->getRepository('echeanciers');

            if (false === $repayment->get($transaction->id_echeancier, 'id_echeancier')) {
                throw new \Exception('Unable to load lender repayment ' . $transaction->id_echeancier);
            }

            /** @var \loans $loan */
            $loan = $this->entityManager->getRepository('loans');

            if (false === $loan->get($repayment->id_loan, 'id_loan')) {
                throw new \Exception('Unable to load loan ' . $repayment->id_loan);
            }

            if ($loan->id_type_contract == \loans::TYPE_CONTRACT_BDC) {
                return $this->applyTaxes($transaction, array(\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE));
            }
        }
    }

    /**
     * @param \transactions $transaction
     * @return int
     */
    private function taxLegalEntityLenderRepaymentInterests(\transactions $transaction)
    {
        return $this->applyTaxes($transaction, array(\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE));
    }
}
