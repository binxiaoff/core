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
    private $oLogger;

    /** @var EntityManager  */
    private $oEntityManager;

    public function __construct(EntityManager $oEntityManager, LoggerInterface $oLogger)
    {
        $this->oLogger        = $oLogger;
        $this->oEntityManager = $oEntityManager;
    }

    /**
     * @param LoggerInterface $oLogger
     */
    public function setLogger(LoggerInterface $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    public function updateIRRUnilend()
    {
        /** @var \unilend_stats $oUnilendStats */
        $oUnilendStats = $this->oEntityManager->getRepository('unilend_stats');

        $aLastUnilendIRR = $this->getLastUnilendIRR();
        $oLastIRRDate    = new \DateTime($aLastUnilendIRR['added']);
        $oNow            = new \DateTime('NOW');
        $oDateDifference = $oNow->diff($oLastIRRDate);

        $fIRRUnilend = $this->calculateIRRUnilend();

        if ($oDateDifference->d == 0) {
            $oUnilendStats->get($aLastUnilendIRR['id_unilend_stat'], 'id_unilend_stat');
            $oUnilendStats->value = $fIRRUnilend;
            $oUnilendStats->update();
        } else {
            $oUnilendStats->value = $fIRRUnilend;
            $oUnilendStats->type_stat = 'IRR';
            $oUnilendStats->create();
        }
    }


    /**
     * @param $aValuesIRR
     * @return string
     * @throws \Exception
     */
    private function calculateIRR($aValuesIRR)
    {
        $aSums = array();
        $aDates = array();

        foreach ($aValuesIRR as $aValues) {
            foreach ($aValues as $date => $value) {
                $aDates[] = $date;
                $aSums[]  = $value;
            }
        }

        $oFinancial   = new \PHPExcel_Calculation_Financial();
        $fXIRR        = $oFinancial->XIRR($aSums, $aDates, self::IRR_GUESS);
        $fXIRRPercent = bcmul($fXIRR, 100, 2);
        if (abs($fXIRRPercent) > 100) {
            throw new \Exception('IRR not in range IRR : ' . $fXIRRPercent);
        }

        return $fXIRRPercent;
    }

    /**
     * @param $iLenderId
     * @return string
     * @throws \Exception
     */
    public function calculateIRRForLender($iLenderId)
    {
        /** @var \lenders_account_stats $oLendersAccountStats */
        $oLendersAccountStats = $this->oEntityManager->getRepository('lenders_account_stats');

        $fStartSQL  = microtime(true);
        $aValuesIRR = $oLendersAccountStats->getValuesForIRRUsingProjectsLastStatusHistoryMaterialized($iLenderId);

        $this->oLogger->info('Calculate IRR for lender ' . $iLenderId . ' - SQL Time : ' . (round(microtime(true) - $fStartSQL, 2)) . ' for ' . count($aValuesIRR). ' lines ');

        $fStartXIRR = microtime(true);
        $fXIRR = $this->calculateIRR($aValuesIRR);

        $this->oLogger->info('Calculate IRR for lender ' . $iLenderId . ' - XIRR Time : ' . (round(microtime(true) - $fStartXIRR, 2)) . ' - Guess : ' . self::IRR_GUESS . ' MAX_INTERATIONS : ' . 100);
        $this->oLogger->info('Calculate IRR for lender ' . $iLenderId . ' - Total time : ' . (round(microtime(true) - $fStartSQL, 2)));

        return $fXIRR;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function calculateIRRUnilend()
    {
        set_time_limit(1000);
        /** @var \unilend_stats $oUnilendStats */
        $oUnilendStats = $this->oEntityManager->getRepository('unilend_stats');
        $fStartSQL  = microtime(true);
        $aValuesIRR = $oUnilendStats->getDataForUnilendIRRUsingProjectsLastStatusMaterialized();
        $this->oLogger->info('Unilend IRR calculation - SQL Time : ' . (round(microtime(true) - $fStartSQL, 2)) . ' for ' . count($aValuesIRR). ' lines ');

        $fStartXIRR = microtime(true);
        $fXIRR      = $this->calculateIRR($aValuesIRR);
        $this->oLogger->info('Unilend IRR calculation - XIRR Time : ' . (round(microtime(true) - $fStartXIRR, 2)) . ' - Guess : ' . self::IRR_GUESS . ' MAX_INTERATIONS : '. 100);
        $this->oLogger->info('Unilend IRR calculation - Total time : ' . (round(microtime(true) - $fStartSQL, 2)));

        return $fXIRR;
    }

    /**
     * @param $sDate
     * @return bool
     */
    public function IRRUnilendNeedsToBeRecalculated($sDate)
    {
        /** @var \lenders_account_stats $oLendersAccountsStats */
        $oLendersAccountsStats = $this->oEntityManager->getRepository('lenders_account_stats');
        /** @var \projects_status_history $oProjectStatusHistory */
        $oProjectStatusHistory = $this->oEntityManager->getRepository('projects_status_history');
        $aProjectStatusTriggeringChange = array(
            \projects_status::REMBOURSEMENT,
            \projects_status::PROBLEME,
            \projects_status::PROBLEME_J_X,
            \projects_status::RECOUVREMENT,
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE,
            \projects_status::DEFAUT
        );

        $iCountProjectStatusChanges    = $oProjectStatusHistory->countProjectStatusChangesOnDate($sDate, $aProjectStatusTriggeringChange);
        $iCountLendersWithLatePayments = $oLendersAccountsStats->getLendersWithLatePaymentsForIRRUsingProjectsLastStatusHistoryMaterialized();
        return count($iCountProjectStatusChanges) > 0 || count($iCountLendersWithLatePayments) > 0 ;
    }

    public function getLastUnilendIRR()
    {
        /** @var \unilend_stats $unilendStats */
        $unilendStats = $this->oEntityManager->getRepository('unilend_stats');
        $aUnilendStats = $unilendStats->select('type_stat = "IRR"', 'added DESC', null, '1');
        return array_shift($aUnilendStats);
    }

    public function addIRRLender($aLender)
    {
        /** @var \lenders_account_stats $oLendersAccountsStats */
        $oLendersAccountsStats = $this->oEntityManager->getRepository('lenders_account_stats');

        $oLendersAccountsStats->id_lender_account = $aLender['id_lender_account'];
        $oLendersAccountsStats->tri_date          = date('Y-m-d H:i:s');
        $oLendersAccountsStats->tri_value         = $this->calculateIRRForLender($aLender['id_lender_account']);
        $oLendersAccountsStats->create();
    }

}
