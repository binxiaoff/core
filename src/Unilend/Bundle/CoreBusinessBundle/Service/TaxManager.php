<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class TaxManager
{
    const TAX_TYPE_EXEMPTED_LENDER = [
        TaxType::TYPE_CSG,
        TaxType::TYPE_SOCIAL_DEDUCTIONS,
        TaxType::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS,
        TaxType::TYPE_SOLIDARITY_DEDUCTIONS,
        TaxType::TYPE_CRDS
    ];

    const TAX_TYPE_TAXABLE_LENDER = [
        TaxType::TYPE_STATUTORY_CONTRIBUTIONS,
        TaxType::TYPE_CSG,
        TaxType::TYPE_SOCIAL_DEDUCTIONS,
        TaxType::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS,
        TaxType::TYPE_SOLIDARITY_DEDUCTIONS,
        TaxType::TYPE_CRDS
    ];

    const TAX_TYPE_FOREIGNER_LENDER    = [TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE];
    const TAX_TYPE_LEGAL_ENTITY_LENDER = [TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE];

    /**
     * @var EntityManagerSimulator
     */
    private $entityManagerSimulator;
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * TaxManager constructor.
     *
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param EntityManager          $entityManager
     */
    public function __construct(EntityManagerSimulator $entityManagerSimulator, EntityManager $entityManager)
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
    }

    /**
     * @param Clients           $client
     * @param \clients_adresses $clientAddress
     *
     * @param $userId
     * @throws \Exception
     */
    public function addTaxToApply(Clients $client, \clients_adresses $clientAddress, $userId)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        $foreigner = 0;
        if ($client->getIdNationalite() <= \nationalites_v2::NATIONALITY_FRENCH && $clientAddress->id_pays_fiscal > PaysV2::COUNTRY_FRANCE) {
            $foreigner = 1;
        } elseif ($client->getIdNationalite() > \nationalites_v2::NATIONALITY_FRENCH && $clientAddress->id_pays_fiscal > PaysV2::COUNTRY_FRANCE) {
            $foreigner = 2;
        }
        /** @var \lenders_imposition_history $lenderImpositionHistory */
        $lenderImpositionHistory                    = $this->entityManagerSimulator->getRepository('lenders_imposition_history');
        $lenderImpositionHistory->id_lender         = $wallet->getId();
        $lenderImpositionHistory->resident_etranger = $foreigner;
        $lenderImpositionHistory->id_pays           = $clientAddress->id_pays_fiscal;
        $lenderImpositionHistory->id_user           = $userId;
        $lenderImpositionHistory->create();
    }

    /**
     * @param Clients            $client
     * @param float              $interestsGross
     * @param UnderlyingContract $underlyingContract
     * @param \DateTime          $taxDate
     *
     * @return array
     */
    public function getLenderRepaymentInterestTax(Clients $client, $interestsGross, \DateTime $taxDate, UnderlyingContract $underlyingContract)
    {
        switch ($client->getType()) {
            case Clients::TYPE_LEGAL_ENTITY:
            case Clients::TYPE_LEGAL_ENTITY_FOREIGNER:
                return $this->getLegalEntityLenderRepaymentInterestsTax($interestsGross);
            case Clients::TYPE_PERSON:
            case Clients::TYPE_PERSON_FOREIGNER:
            default:
                return $this->getNaturalPersonLenderRepaymentInterestsTax($client, $interestsGross, $taxDate, $underlyingContract);
        }
    }

    private function getLegalEntityLenderRepaymentInterestsTax($interestsGross)
    {
        return $this->calculateTaxes($interestsGross, [TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]);
    }

    private function getNaturalPersonLenderRepaymentInterestsTax(Clients $client, $interestsGross, \DateTime $taxDate, UnderlyingContract $underlyingContract = null)
    {
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::LENDER);
        if (null === $wallet) {
            throw new \Exception('Unable to load lender wallet with client ID ' . $client->getIdClient());
        }

        /** @var \lenders_imposition_history $lenderTaxationHistory */
        $lenderTaxationHistory = $this->entityManagerSimulator->getRepository('lenders_imposition_history');

        if (false === $lenderTaxationHistory->loadLastTaxationHistory($wallet->getId())) {
            /**
             * throw new \Exception('Unable to load lender taxation history with lender ID ' . $lender->id_lender_account);
             */
            /** @todo this is a temporary fix, uncomment the line above and remove this line once the TMA-761 was done */
            $lenderTaxationHistory->resident_etranger = 0;
        }

        if (0 == $lenderTaxationHistory->resident_etranger) {
            /** @var \lender_tax_exemption $taxExemption */
            $taxExemption = $this->entityManagerSimulator->getRepository('lender_tax_exemption');

            if ($taxExemption->get($wallet->getId() . '" AND year = "' . $taxDate->format('Y') . '" AND iso_country = "FR', 'id_lender')) { // @todo i18n
                return $this->calculateTaxes($interestsGross, [
                    TaxType::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS,
                    TaxType::TYPE_CRDS,
                    TaxType::TYPE_CSG,
                    TaxType::TYPE_SOLIDARITY_DEDUCTIONS,
                    TaxType::TYPE_SOCIAL_DEDUCTIONS
                ]);
            } else {
                return $this->calculateTaxes($interestsGross, [
                    TaxType::TYPE_STATUTORY_CONTRIBUTIONS,
                    TaxType::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS,
                    TaxType::TYPE_CRDS,
                    TaxType::TYPE_CSG,
                    TaxType::TYPE_SOLIDARITY_DEDUCTIONS,
                    TaxType::TYPE_SOCIAL_DEDUCTIONS
                ]);
            }
        } else {
            if ($underlyingContract instanceof UnderlyingContract && UnderlyingContract::CONTRACT_IFP !== $underlyingContract->getLabel()) {
                return $this->calculateTaxes($interestsGross, [TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE]);
            }
        }

        return [];
    }

    /**
     * @param float $amount
     * @param array $taxTypes
     *
     * @return array
     */
    public function calculateTaxes($amount, array $taxTypes)
    {
        $taxTypeRepo = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TaxType');
        $taxes       = [];

        foreach ($taxTypes as $taxTypeId) {
            $taxType           = $taxTypeRepo->find($taxTypeId);
            $taxes[$taxTypeId] = round(bcmul($amount, bcdiv($taxType->getRate(), 100, 4), 4), 2);
        }

        return $taxes;
    }
}
