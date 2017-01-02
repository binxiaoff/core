<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class TaxManager
{
    /**
     * @var EntityManagerSimulator
     */
    private $entityManager;
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * TaxManager constructor.
     *
     * @param EntityManagerSimulator $entityManager
     * @param EntityManager $em
     */
    public function __construct(EntityManagerSimulator $entityManager, EntityManager $em)
    {
        $this->entityManager = $entityManager;
        $this->em = $em;
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
            case \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL:
                return 0;
        }
    }

    /**
     * @param \transactions $transaction
     * @param array         $taxesTypes
     * @return int
     */
    private function applyTaxes(\transactions &$transaction, array $taxesTypes)
    {
        /** @var \tax $tax */
        $tax = $this->entityManager->getRepository('tax');
        /** @var \tax_type $taxType */
        $taxType = $this->entityManager->getRepository('tax_type');

        $taxes          = [];
        $totalTaxAmount = 0;

        foreach ($taxesTypes as $taxTypeId) {
            $taxType->get($taxTypeId);
            $taxAmount = round($transaction->montant * $taxType->rate / 100);
            $totalTaxAmount += $taxAmount;

            $taxes[] = [
                'id_tax_type'    => $taxTypeId,
                'id_transaction' => $transaction->id_transaction,
                'amount'         => $taxAmount,
                'added'          => date('Y-m-d H:i:s'),
                'updated'        => date('Y-m-d H:i:s'),
            ];
        }

        $transaction->montant -= $totalTaxAmount;
        $transaction->update();
        $tax->multiInsert($taxes);

        return $totalTaxAmount;
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
                return $this->applyTaxes($transaction, [\tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS, \tax_type::TYPE_CRDS, \tax_type::TYPE_CSG, \tax_type::TYPE_SOLIDARITY_DEDUCTIONS, \tax_type::TYPE_SOCIAL_DEDUCTIONS]);
            } else {
                return $this->applyTaxes($transaction, [\tax_type::TYPE_INCOME_TAX, \tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS, \tax_type::TYPE_CRDS, \tax_type::TYPE_CSG, \tax_type::TYPE_SOLIDARITY_DEDUCTIONS, \tax_type::TYPE_SOCIAL_DEDUCTIONS]);
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

            /** @var \underlying_contract $contract */
            $contract = $this->entityManager->getRepository('underlying_contract');

            if (false === $contract->get($loan->id_type_contract)) {
                throw new \Exception('Unable to load underlying contract ' . $loan->id_type_contract);
            }

            if ($contract->label != \underlying_contract::CONTRACT_IFP) {
                return $this->applyTaxes($transaction, [\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]);
            }
        }
    }

    /**
     * @param \transactions $transaction
     * @return int
     */
    private function taxLegalEntityLenderRepaymentInterests(\transactions $transaction)
    {
        return $this->applyTaxes($transaction, [\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]);
    }

    /**
     * @param \clients $client
     * @param \lenders_accounts $lenderAccount
     * @param \clients_adresses $clientAddress
     * @param int $userId
     */
    public function addTaxToApply(\clients $client, \lenders_accounts $lenderAccount, \clients_adresses $clientAddress, $userId)
    {
        $foreigner = 0;
        if ($client->id_nationalite <= 1 && $clientAddress->id_pays_fiscal > 1) {
            $foreigner = 1;
        } elseif ($client->id_nationalite > 1 && $clientAddress->id_pays_fiscal > 1) {
            $foreigner = 2;
        }
        /** @var \lenders_imposition_history $lenderImpositionHistory */
        $lenderImpositionHistory                    = $this->entityManager->getRepository('lenders_imposition_history');
        $lenderImpositionHistory->id_lender         = $lenderAccount->id_lender_account;
        $lenderImpositionHistory->resident_etranger = $foreigner;
        $lenderImpositionHistory->id_pays           = $clientAddress->id_pays_fiscal;
        $lenderImpositionHistory->id_user           = $userId;
        $lenderImpositionHistory->create();
    }

    public function getLenderRepaymentInterestTax(Echeanciers $repaymentSchedule)
    {
        $lenderAccountId = $repaymentSchedule->getIdLoan()->getIdLender();
        $accountMatching = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idLenderAccount' => $lenderAccountId]);
        $wallet          = $accountMatching->getIdWallet();
        $interestsGross  = round(bcdiv(bcsub($repaymentSchedule->getInterets(), $repaymentSchedule->getInteretsRembourses()), 100, 4), 2);

        switch ($wallet->getIdClient()->getType()) {
            case Clients::TYPE_LEGAL_ENTITY:
            case Clients::TYPE_LEGAL_ENTITY_FOREIGNER:
                return $this->getLegalEntityLenderRepaymentInterestsTax($interestsGross);
            case Clients::TYPE_PERSON:
            case Clients::TYPE_PERSON_FOREIGNER:
            default:
                $underlyingContract = $repaymentSchedule->getIdLoan()->getIdTypeContract();
                return $this->getNaturalPersonLenderRepaymentInterestsTax($wallet->getIdClient(), $interestsGross, $repaymentSchedule->getDateEcheanceReel(), $underlyingContract);
        }
    }

    private function getLegalEntityLenderRepaymentInterestsTax($interestsGross)
    {
        return $this->calculateTaxes($interestsGross, [\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]);
    }

    private function getNaturalPersonLenderRepaymentInterestsTax(Clients $client, $interestsGross, \DateTime $taxDate, UnderlyingContract $underlyingContract = null)
    {
        /** @var \lenders_accounts $lender */
        $lender = $this->entityManager->getRepository('lenders_accounts');

        if (false === $lender->get($client->getIdClient(), 'id_client_owner')) {
            throw new \Exception('Unable to load lender with client ID ' . $client->getIdClient());
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

            if ($taxExemption->get($lender->id_lender_account . '" AND year = "' . $taxDate->format('Y') . '" AND iso_country = "FR', 'id_lender')) { // @todo i18n
                return $this->calculateTaxes($interestsGross, [\tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS, \tax_type::TYPE_CRDS, \tax_type::TYPE_CSG, \tax_type::TYPE_SOLIDARITY_DEDUCTIONS, \tax_type::TYPE_SOCIAL_DEDUCTIONS]);
            } else {
                return $this->calculateTaxes($interestsGross, [\tax_type::TYPE_INCOME_TAX, \tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS, \tax_type::TYPE_CRDS, \tax_type::TYPE_CSG, \tax_type::TYPE_SOLIDARITY_DEDUCTIONS, \tax_type::TYPE_SOCIAL_DEDUCTIONS]);
            }
        } else {
            if ($underlyingContract instanceof UnderlyingContract && UnderlyingContract::CONTRACT_IFP !== $underlyingContract->getLabel()) {
                return $this->calculateTaxes($interestsGross, [\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]);
            }
        }

        return [];
    }

    public function calculateTaxes($amount, array $taxTypes)
    {
        $taxTypeRepo = $this->em->getRepository('UnilendCoreBusinessBundle:TaxType');
        $taxes          = [];

        foreach ($taxTypes as $taxTypeId) {
            $taxType = $taxTypeRepo->find($taxTypeId);
            $taxes[$taxTypeId] = round(bcmul($amount, bcdiv($taxType->getRate(), 100, 4), 4), 2);
        }

        return $taxes;
    }
}
