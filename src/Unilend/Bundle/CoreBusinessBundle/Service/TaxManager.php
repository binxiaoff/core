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

    const TAX_TYPE_FOREIGNER_LENDER    = [TaxType::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE_PERSON];
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
     * @param Clients $client
     * @param int     $userId
     *
     * @throws \Exception
     */
    public function addTaxToApply(Clients $client, int $userId): void
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        if (null === $client->getIdAddress()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' has no validated main address');
        }

        $wallet    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $foreigner = 0;

        if ($client->getIdNationalite() === \nationalites_v2::NATIONALITY_FRENCH && $client->getIdAddress()->getIdCountry()->getIdPays() !== PaysV2::COUNTRY_FRANCE) {
            $foreigner = 1;
        } elseif ($client->getIdNationalite() !== \nationalites_v2::NATIONALITY_FRENCH && $client->getIdAddress()->getIdCountry()->getIdPays() !== PaysV2::COUNTRY_FRANCE) {
            $foreigner = 2;
        }

        /** @var \lenders_imposition_history $lenderImpositionHistory */
        $lenderImpositionHistory                    = $this->entityManagerSimulator->getRepository('lenders_imposition_history');
        $lenderImpositionHistory->id_lender         = $wallet->getId();
        $lenderImpositionHistory->resident_etranger = $foreigner;
        $lenderImpositionHistory->id_pays           = $client->getIdAddress()->getIdCountry()->getIdPays();
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
        if (false === $client->isNaturalPerson()) {
            return $this->getLegalEntityLenderRepaymentInterestsTax($interestsGross);
        } else {
            return $this->getNaturalPersonLenderRepaymentInterestsTax($client, $interestsGross, $taxDate, $underlyingContract);
        }
    }

    /**
     * @param float $interestsGross
     *
     * @return array
     */
    private function getLegalEntityLenderRepaymentInterestsTax($interestsGross)
    {
        return $this->calculateTaxes($interestsGross, self::TAX_TYPE_LEGAL_ENTITY_LENDER);
    }

    /**
     * @param Clients                 $client
     * @param float                   $interestsGross
     * @param \DateTime               $taxDate
     * @param UnderlyingContract|null $underlyingContract
     *
     * @return array
     * @throws \Exception
     */
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
            $LenderTaxExemptionRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:LenderTaxExemption');
            $taxExemption = $LenderTaxExemptionRepository->findOneBy(['idLender' => $wallet, 'year' => $taxDate->format('Y'), 'isoCountry' => 'FR']);

            if (null !== $taxExemption) { // @todo i18n
                return $this->calculateTaxes($interestsGross, self::TAX_TYPE_EXEMPTED_LENDER);
            } else {
                return $this->calculateTaxes($interestsGross, self::TAX_TYPE_TAXABLE_LENDER);
            }
        } else {
            if ($underlyingContract instanceof UnderlyingContract && UnderlyingContract::CONTRACT_IFP !== $underlyingContract->getLabel()) {
                return $this->calculateTaxes($interestsGross, self::TAX_TYPE_FOREIGNER_LENDER);
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
