<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\LenderStatistic;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnilendStats;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

/**
 * Class IRRManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class IRRManager
{
    const IRR_GUESS                       = 0.1;
    const IRR_UNILEND_RISK_PERIOD_1_START = '2013-01-01';
    const IRR_UNILEND_RISK_PERIOD_1_END   = '2014-12-31';
    const IRR_UNILEND_RISK_PERIOD_2_START = '2015-01-01';
    const IRR_UNILEND_RISK_PERIOD_2_END   = '2015-08-31';
    const IRR_UNILEND_RISK_PERIOD_3_START = '2015-09-01';
    const IRR_UNILEND_RISK_PERIOD_3_END   = '2016-08-31';
    const IRR_UNILEND_RISK_PERIOD_4_START = '2016-09-01';

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerSimulator  */
    private $entityManagerSimulator;

    /** @var  EntityManager */
    private $entityManager;

    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        LoggerInterface $logger
    )
    {
        $this->logger                 = $logger;
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function addIRRUnilend()
    {
        $unilendIrr = new UnilendStats();
        $unilendIrr->setValue($this->calculateIRRUnilend());
        $unilendIrr->setTypeStat(UnilendStats::TYPE_STAT_IRR);
        $this->entityManager->persist($unilendIrr);
        $this->entityManager->flush($unilendIrr);
    }


    /**
     * @param $aValuesIRR
     * @return string
     * @throws \Exception
     */
    private function calculateIRR($valuesIRR)
    {
        $sums  = [];
        $dates = [];

        foreach ($valuesIRR as $value) {
            $dates[] = $value['date'];
            $sums[]  = $value['amount'];
        }

        $financial = new \PHPExcel_Calculation_Financial();
        $xirr      = $financial->XIRR($sums, $dates, self::IRR_GUESS);

        if (abs($xirr) > 1 || abs($xirr) < 0.0000000001 ) {
            throw new \Exception('IRR not in range IRR : ' . $xirr);
        }

        return round(bcmul($xirr, 100, 3), 2);
    }

    /**
     * @param Wallet $wallet
     * @param $lenderId
     *
     * @return string
     */
    public function calculateIRRForLender(Wallet $wallet)
    {
        $lenderStatisticRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:LenderStatistic');
        $valuesIRR                 = $lenderStatisticRepository->getValuesForIRR($wallet->getId());

        return $this->calculateIRR($valuesIRR);
    }

    /**
     * @return string
     */
    public function calculateIRRUnilend()
    {
        set_time_limit(1000);

        $unilendStatsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats');
        $valuesIRR              = $unilendStatsRepository->getDataForUnilendIRR();

        return $this->calculateIRR($valuesIRR);
    }

    /**
     * @param \DateTime $date
     *
     * @return bool
     */
    public function IRRUnilendNeedsToBeRecalculated(\DateTime $date)
    {
        /** @var WalletRepository $walletRepository */
        $walletRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManagerSimulator->getRepository('projects_status_history');
        $projectStatusTriggeringChange = [
            \projects_status::REMBOURSEMENT,
            \projects_status::PROBLEME,
            \projects_status::PROBLEME_J_X,
            \projects_status::RECOUVREMENT,
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE,
            \projects_status::DEFAUT
        ];

        $lendersWithLatePayments   = $walletRepository->getLendersWalletsWithLatePaymentsForIRR();
        $countProjectStatusChanges = $projectStatusHistory->countProjectStatusChangesOnDate($date->format('Y-m-d'), $projectStatusTriggeringChange);

        return count($countProjectStatusChanges) > 0 || count($lendersWithLatePayments) > 0 ;
    }

    /**
     * @param Wallet $wallet
     * @param int    $lenderId
     */
    public function addIRRLender(Wallet $wallet)
    {
        $status = LenderStatistic::STAT_VALID_OK;

        try {
            $lenderIRR = $this->calculateIRRForLender($wallet);
        } catch (\Exception $irrException) {
            $status    = LenderStatistic::STAT_VALID_NOK;
            $lenderIRR = 0;
        }

        $lenderStat = new LenderStatistic();
        $lenderStat->setIdWallet($wallet);
        $lenderStat->setTypeStat(LenderStatistic::TYPE_STAT_IRR);
        $lenderStat->setStatus($status);
        $lenderStat->setValue($lenderIRR);
        $this->entityManager->persist($lenderStat);
        $this->entityManager->flush($lenderStat);
    }

    /**
     * @return null|UnilendStats
     */
    public function getLastUnilendIRR()
    {
        $unilendStatsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats');
        return $unilendStatsRepository->findOneBy(['typeStat' => UnilendStats::TYPE_STAT_IRR], ['added' => 'DESC']);
    }

    /**
     * @param string $cohortStartDate
     * @param string $cohortEndDate
     *
     * @return string
     */
    public function getUnilendIRRByCohort($cohortStartDate, $cohortEndDate)
    {
        set_time_limit(1000);

        $unilendStatsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats');
        $valuesIRR = $unilendStatsRepository->getIRRValuesByCohort($cohortStartDate, $cohortEndDate);

        return $this->calculateIRR($valuesIRR);
    }

    public function addIRRForAllRiskPeriodCohort()
    {
        $cohort1 = new UnilendStats();
        $cohort1->setValue($this->getUnilendIRRByCohort(self::IRR_UNILEND_RISK_PERIOD_1_START, self::IRR_UNILEND_RISK_PERIOD_1_END));
        $cohort1->setTypeStat('IRR_cohort_' . self::IRR_UNILEND_RISK_PERIOD_1_START . '_' . self::IRR_UNILEND_RISK_PERIOD_1_END);
        $this->entityManager->persist($cohort1);

        $cohort2 = new UnilendStats();
        $cohort2->setValue($this->getUnilendIRRByCohort(self::IRR_UNILEND_RISK_PERIOD_2_START, self::IRR_UNILEND_RISK_PERIOD_2_END));
        $cohort2->setTypeStat('IRR_cohort_' . self::IRR_UNILEND_RISK_PERIOD_2_START . '_' . self::IRR_UNILEND_RISK_PERIOD_2_END);
        $this->entityManager->persist($cohort2);

        $cohort3 = new UnilendStats();
        $cohort3->setValue($this->getUnilendIRRByCohort(self::IRR_UNILEND_RISK_PERIOD_3_START, self::IRR_UNILEND_RISK_PERIOD_3_END));
        $cohort3->setTypeStat('IRR_cohort_' . self::IRR_UNILEND_RISK_PERIOD_3_START . '_' . self::IRR_UNILEND_RISK_PERIOD_3_END);
        $this->entityManager->persist($cohort3);

        $cohort4 = new UnilendStats();
        $cohort4->setValue($this->getUnilendIRRByCohort(self::IRR_UNILEND_RISK_PERIOD_4_START, date('Y-m-d')));
        $cohort4->setTypeStat('IRR_cohort_' . 'IRR_cohort_' . self::IRR_UNILEND_RISK_PERIOD_4_START . '_' . date('Y-m-d'));
        $this->entityManager->persist($cohort4);

        $this->entityManager->flush();
    }
}
