<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

/**
 * Class IRRManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class IRRManager
{
    const IRR_GUESS = 0.1;

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManager  */
    private $entityManager;

    public function __construct(EntityManager $entityManager, LoggerInterface $logger)
    {
        $this->logger        = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function updateIRRUnilend()
    {
        /** @var \unilend_stats $unilendStats */
        $unilendStats = $this->entityManager->getRepository('unilend_stats');

        $lastUnilendIRR = $this->getLastUnilendIRR();
        $lastIRRDate    = new \DateTime($lastUnilendIRR['added']);
        $now            = new \DateTime('NOW');
        $dateDifference = $now->diff($lastIRRDate);

        $IRRUnilend = $this->calculateIRRUnilend();

        if ($dateDifference->d == 0) {
            $unilendStats->get($lastUnilendIRR['id_unilend_stat'], 'id_unilend_stat');
            $unilendStats->value =  bcmul($IRRUnilend, 100, 2);
            $unilendStats->update();
        } else {
            $unilendStats->value = bcmul($IRRUnilend, 100, 2);
            $unilendStats->type_stat = 'IRR';
            $unilendStats->create();
        }
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

        foreach ($valuesIRR as $values) {
            foreach ($values as $date => $value) {
                $dates[] = $date;
                $sums[]  = $value;
            }
        }

        $financial   = new \PHPExcel_Calculation_Financial();
        return $financial->XIRR($sums, $dates, self::IRR_GUESS);
    }

    /**
     * @param $lenderId
     * @return string
     */
    public function calculateIRRForLender($lenderId)
    {
        /** @var \lenders_account_stats $lendersAccountStats */
        $lendersAccountStats = $this->entityManager->getRepository('lenders_account_stats');
        $valuesIRR = $lendersAccountStats->getValuesForIRR($lenderId);

        return $this->calculateIRR($valuesIRR);
    }

    /**
     * @return string
     */
    public function calculateIRRUnilend()
    {
        set_time_limit(1000);
        /** @var \unilend_stats $unilendStats */
        $unilendStats = $this->entityManager->getRepository('unilend_stats');
        $valuesIRR = $unilendStats->getDataForUnilendIRR();

        return $this->calculateIRR($valuesIRR);
    }

    /**
     * @param $sDate
     * @return bool
     */
    public function IRRUnilendNeedsToBeRecalculated($date)
    {
        /** @var \lenders_account_stats $lendersAccountsStats */
        $lendersAccountsStats = $this->entityManager->getRepository('lenders_account_stats');
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');
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

        $countProjectStatusChanges    = $projectStatusHistory->countProjectStatusChangesOnDate($date, $projectStatusTriggeringChange);
        $countLendersWithLatePayments = $lendersAccountsStats->getLendersWithLatePaymentsForIRR();
        return count($countProjectStatusChanges) > 0 || count($countLendersWithLatePayments) > 0 ;
    }

    public function addIRRLender($lenderId)
    {
        $lenderIRR = $this->calculateIRRForLender($lenderId);
        $status    = $this->checkIRRValidity($lenderIRR);

        /** @var \lenders_account_stats $lendersAccountsStats */
        $lendersAccountsStats = $this->entityManager->getRepository('lenders_account_stats');
        $lendersAccountsStats->id_lender_account = $lenderId;
        $lendersAccountsStats->date              = date('Y-m-d H:i:s');
        $lendersAccountsStats->value             = bcmul($lenderIRR, 100, 2);
        $lendersAccountsStats->type_stat         = \lenders_account_stats::STAT_TYPE_IRR;
        $lendersAccountsStats->status            = $status;

        $lendersAccountsStats->create();
    }

    private function checkIRRValidity($irr)
    {
        if (abs($irr) < 1) {
            return \lenders_account_stats::STAT_VALID_OK;
        } else {
            return \lenders_account_stats::STAT_VALID_NOK;
        }
    }

    public function getLastUnilendIRR()
    {
        /** @var \unilend_stats $unilendStats */
        $unilendStats = $this->entityManager->getRepository('unilend_stats');
        return $unilendStats->select('type_stat = "IRR"', 'added DESC', null, '1')[0];
    }

    public function getUnilendIRRByCohort($year)
    {
        set_time_limit(1000);
        /** @var \unilend_stats $unilendStats */
        $unilendStats = $this->entityManager->getRepository('unilend_stats');
        $valuesIRR = $unilendStats->getIRRValuesByCohort($year);

        return bcmul($this->calculateIRR($valuesIRR), 100, 2);
    }

    public function getUnilendIRRForCohort20132014()
    {
        set_time_limit(1000);
        /** @var \unilend_stats $unilendStats */
        $unilendStats  = $this->entityManager->getRepository('unilend_stats');
        $valuesIRR2013 = $unilendStats->getIRRValuesByCohort(2013);
        $valuesIRR2014 = $unilendStats->getIRRValuesByCohort(2014);
        $valuesIRR     = array_merge($valuesIRR2013, $valuesIRR2014);

        return bcmul($this->calculateIRR($valuesIRR), 100, 2);
    }

}
