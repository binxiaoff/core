<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Factures;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class ProjectManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;
    /** @var BidManager */
    private $bidManager;

    public function __construct(EntityManagerSimulator $entityManagerSimulator, EntityManager $entityManager, BidManager $bidManager)
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->bidManager             = $bidManager;
    }

    /**
     * @param \projects $project
     *
     * @return \DateTime
     */
    public function getProjectEndDate(\projects $project)
    {
        return $project->date_fin != '0000-00-00 00:00:00' ? new \DateTime($project->date_fin) : new \DateTime($project->date_retrait);
    }

    /**
     * @param \projects $oProject
     *
     * @return bool
     */
    public function isFunded(\projects $oProject)
    {
        /** @var \bids $oBid */
        $oBid      = $this->entityManagerSimulator->getRepository('bids');
        $iBidTotal = $oBid->getSoldeBid($oProject->id_project);

        if ($iBidTotal >= $oProject->amount) {
            return true;
        }

        return false;
    }

    /**
     * @param \projects $project
     *
     * @return array
     */
    public function getBidsSummary(\projects $project)
    {
        /** @var \bids $bid */
        $bid = $this->entityManagerSimulator->getRepository('bids');

        return $bid->getBidsSummary($project->id_project);
    }

    /**
     * @return array
     */
    public function getPossibleProjectPeriods()
    {
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $settings->get('Durée des prêts autorisées', 'type');

        return explode(',', $settings->value);
    }

    /**
     * @return int
     */
    public function getMaxProjectAmount()
    {
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $settings->get('Somme à emprunter max', 'type');

        return (int) $settings->value;
    }

    /**
     * @return int
     */
    public function getMinProjectAmount()
    {
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $settings->get('Somme à emprunter min', 'type');

        return (int) $settings->value;
    }

    /**
     * @param int $amount
     *
     * @return int
     */
    public function getAverageFundingDuration($amount)
    {
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $settings->get('Durée moyenne financement', 'type');

        $projectAverageFundingDuration = 15;
        foreach (json_decode($settings->value) as $averageFundingDuration) {
            if ($amount >= $averageFundingDuration->min && $amount <= $averageFundingDuration->max) {
                $projectAverageFundingDuration = round($averageFundingDuration->heures / 24);
            }
        }

        return $projectAverageFundingDuration;
    }

    /**
     * @param int $amount
     * @param int $duration
     * @param int $repaymentCommissionRate
     *
     * @return int[]
     */
    public function getMonthlyPaymentBoundaries($amount, $duration, $repaymentCommissionRate = \projects::DEFAULT_COMMISSION_RATE_REPAYMENT)
    {
        $financialCalculation = new \PHPExcel_Calculation_Financial();

        /** @var \project_period $projectPeriod */
        $projectPeriod = $this->entityManagerSimulator->getRepository('project_period');
        $projectPeriod->getPeriod($duration);

        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $this->entityManagerSimulator->getRepository('project_rate_settings');
        $rateSettings        = $projectRateSettings->getSettings(null, $projectPeriod->id_period);

        $minimumRate = min(array_column($rateSettings, 'rate_min'));
        $maximumRate = max(array_column($rateSettings, 'rate_max'));

        $taxType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT);
        $vatRate = $taxType->getRate() / 100;

        $commissionRateRepayment = round(bcdiv($repaymentCommissionRate, 100, 4), 2);
        $commission              = ($financialCalculation->PMT($commissionRateRepayment / 12, $duration, -$amount) - $financialCalculation->PMT(0, $duration, -$amount)) * (1 + $vatRate);

        return [
            'minimum' => round($financialCalculation->PMT($minimumRate / 100 / 12, $duration, -$amount) + $commission),
            'maximum' => round($financialCalculation->PMT($maximumRate / 100 / 12, $duration, -$amount) + $commission)
        ];
    }

    /**
     * @param Projects $project
     *
     * @return mixed
     * @throws \Exception
     */
    public function getProjectRateRangeId(Projects $project)
    {
        if (empty($project->getPeriod())) {
            throw new \Exception('project period not set.');
        }

        if (empty($project->getRisk())) {
            throw new \Exception('project risk not set.');
        }

        /** @var \project_period $projectPeriod */
        $projectPeriod = $this->entityManagerSimulator->getRepository('project_period');

        if ($projectPeriod->getPeriod($project->getPeriod())) {
            /** @var \project_rate_settings $projectRateSettings */
            $projectRateSettings = $this->entityManagerSimulator->getRepository('project_rate_settings');
            $rateSettings        = $projectRateSettings->getSettings($project->getRisk(), $projectPeriod->id_period);

            if (empty($rateSettings)) {
                throw new \Exception('No rate settings found for the project.');
            }
            if (count($rateSettings) === 1) {
                return $rateSettings[0]['id_rate'];
            } else {
                throw new \Exception('More than one rate settings found for the project.');
            }
        } else {
            throw new \Exception('Period not found for the project.');
        }
    }

    /**
     * @param \projects $project
     *
     * @return bool
     */
    public function isRateMinReached(\projects $project)
    {
        $rateRange = $this->bidManager->getProjectRateRange($project);
        /** @var \bids $bid */
        $bid             = $this->entityManagerSimulator->getRepository('bids');
        $totalBidRateMin = $bid->getSoldeBid($project->id_project, $rateRange['rate_min'], [Bids::STATUS_PENDING, Bids::STATUS_ACCEPTED]);

        return $totalBidRateMin >= $project->amount;
    }

    /**
     * @param Projects $project
     * @param boolean  $inclTax
     *
     * @return float
     */
    public function getCommissionFunds(Projects $project, $inclTax)
    {
        $invoice        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Factures')->findOneBy(['idProject' => $project, 'typeCommission' => Factures::TYPE_COMMISSION_FUNDS]);
        $commissionRate = round(bcdiv($project->getCommissionRateFunds(), 100, 5), 4);
        $commission     = round(bcmul($project->getAmount(), $commissionRate, 4), 2);
        if (null !== $invoice) {
            $commission = round(bcdiv($invoice->getMontantHt(), 100, 4), 2);
        }

        if ($inclTax) {
            if (null !== $invoice) {
                $commission = round(bcdiv($invoice->getMontantTtc(), 100, 4), 2);
            } else {
                $vatTax     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:TaxType')->find(TaxType::TYPE_VAT);
                $vatRate    = bcadd(1, bcdiv($vatTax->getRate(), 100, 4), 4);
                $commission = round(bcmul($vatRate, $commission, 4), 2);
            }
        }

        return $commission;
    }

    /**
     * @param Projects $project
     * @param boolean  $includePendingRequest
     *
     * @return string
     */
    public function getRestOfFundsToRelease(Projects $project, $includePendingRequest)
    {
        $fundsToRelease = bcsub($project->getAmount(), $this->getCommissionFunds($project, true), 2);
        if ($includePendingRequest) {
            $status = [Virements::STATUS_CLIENT_DENIED, Virements::STATUS_DENIED];
        } else {
            $status = [Virements::STATUS_CLIENT_DENIED, Virements::STATUS_DENIED, Virements::STATUS_CLIENT_VALIDATED, Virements::STATUS_PENDING];
        }
        $wireTransferOuts = $project->getWireTransferOuts();
        foreach ($wireTransferOuts as $wireTransferOut) {
            if (false === in_array($wireTransferOut->getStatus(), $status)) {
                $fundsToRelease = bcsub($fundsToRelease, round(bcdiv($wireTransferOut->getMontant(), 100, 4), 2), 2);
            }
        }

        return $fundsToRelease;
    }

    /**
     * @param Projects $project
     *
     * @return bool
     */
    public function isHealthy(Projects $project)
    {
        if (
            null === $project->getCloseOutNettingDate()
            && 0 === count($project->getDebtCollectionMissions())
            && CompanyStatus::STATUS_IN_BONIS === $project->getIdCompany()->getIdStatus()->getLabel()
            && 0 === $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->getOverdueScheduleCount($project)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Projects $project
     *
     * @return array
     */
    public function getOverdueAmounts(Projects $project): array
    {
        if (null === $project->getCloseOutNettingDate()) {
            $overdueAmounts = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->getTotalOverdueAmounts($project);
        } else {
            $overdueAmounts = $this->getCloseOutNettingRemainingAmounts($project);
        }

        return $overdueAmounts;
    }

    /**
     * @param Projects $project
     *
     * @return array
     */
    public function getRemainingAmounts(Projects $project): array
    {
        if (null === $project->getCloseOutNettingDate()) {
            $remainingAmounts = $this->entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')->getRemainingAmountsByProject($project);
        } else {
            $remainingAmounts = $this->getCloseOutNettingRemainingAmounts($project);
        }

        return $remainingAmounts;
    }

    /**
     * @param Projects $project
     *
     * @return array
     */
    private function getCloseOutNettingRemainingAmounts(Projects $project): array
    {
        $remainingAmounts = ['capital' => 0, 'interest' => 0, 'commission' => 0];

        if ($project->getCloseOutNettingDate()) {
            $closeOutNettingPayment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingPayment')->findOneBy(['idProject' => $project]);
            if ($closeOutNettingPayment) {
                $remainingAmounts = [
                    'capital'    => round(bcsub($closeOutNettingPayment->getCapital(), $closeOutNettingPayment->getPaidCapital(), 4), 2),
                    'interest'   => round(bcsub($closeOutNettingPayment->getInterest(), $closeOutNettingPayment->getPaidInterest(), 4), 2),
                    'commission' => round(bcsub($closeOutNettingPayment->getCommissionTaxIncl(), $closeOutNettingPayment->getPaidCommissionTaxIncl(), 4), 2),
                ];
            }
        }

        return $remainingAmounts;
    }

    /**
     * @param Loans $loan
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCreditorClaimAmounts(Loans $loan)
    {
        if ($loan->getProject()->getCloseOutNettingDate()) {
            $closeOutNettingRepayment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CloseOutNettingRepayment')->findOneBy(['idLoan' => $loan]);
            $remainingCapital         = round(bcsub($closeOutNettingRepayment->getCapital(), $closeOutNettingRepayment->getRepaidCapital(), 4), 2);
            $remainingInterest        = round(bcsub($closeOutNettingRepayment->getInterest(), $closeOutNettingRepayment->getRepaidInterest(), 4), 2);
            $expired                  = round(bcadd($remainingCapital, $remainingInterest, 4), 2);
            $toExpire                 = 0;
        } else {
            $collectiveProceedingStatus = [
                CompanyStatus::STATUS_PRECAUTIONARY_PROCESS,
                CompanyStatus::STATUS_RECEIVERSHIP,
                CompanyStatus::STATUS_COMPULSORY_LIQUIDATION
            ];
            $companyStatusHistory       = $this->entityManager
                ->getRepository('UnilendCoreBusinessBundle:CompanyStatusHistory')
                ->findFirstHistoryByCompanyAndStatus($loan->getProject()->getIdCompany(), $collectiveProceedingStatus);

            $repaymentScheduleRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
            $expired                     = $repaymentScheduleRepository->getTotalOverdueAmountByLoan($loan, $companyStatusHistory->getChangedOn());
            $toExpire                    = round(bcsub(
                $repaymentScheduleRepository->getRemainingCapitalByLoan($loan),
                $repaymentScheduleRepository->getOverdueCapitalByLoan($loan, $companyStatusHistory->getChangedOn()),
                4
            ), 2);
        }

        return ['expired' => $expired, 'to_expired' => $toExpire];
    }
}
