<?php

namespace Unilend\Service;

use Psr\Log\LoggerInterface;
use Unilend\core\Loader;
use Unilend\Service\Simulator\EntityManager;

/**
 * Class IRRManager
 * @package Unilend\Service
 */
class IRRManager
{
    const IRR_GUESS = 0.1;

    /** @var  LoggerInterface */
    private $oLogger;

    /** @var array */
    private $aConfig;

    /** @var EntityManager  */
    private $oEntityManager;

    public function __construct(EntityManager $oEntityManager, LoggerInterface $oLogger)
    {
        $this->aConfig = Loader::loadConfig();
        $this->oLogger = $oLogger;
        $this->oEntityManager = $oEntityManager;

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

        $oFinancial = new \PHPExcel_Calculation_Financial();
        $fXIRR      = bcmul($oFinancial->XIRR($aSums, $aDates, self::IRR_GUESS), 100, 2);
        if (abs($fXIRR) > 100) {
            throw new \Exception('IRR not in range IRR : ' . $fXIRR);
        }

        return $fXIRR;
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
        $this->oLogger->info('Lender ' . $iLenderId . ' - SQL Time : ' . (round(microtime(true) - $fStartSQL, 2)) . ' for ' . count($aValuesIRR). ' lines ');

        $fStartXIRR = microtime(true);
        $fXIRR = $this->calculateIRR($aValuesIRR);

        $this->oLogger->info('Lender ' . $iLenderId . ' - XIRR Time : ' . (round(microtime(true) - $fStartXIRR, 2)) . ' - Guess : ' . self::IRR_GUESS . ' MAX_INTERATIONS : ' . 100);
        $this->oLogger->info('Lender ' . $iLenderId . ' - Total time : ' . (round(microtime(true) - $fStartSQL, 2)));

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
        $this->oLogger->info('Unilend - SQL Time : ' . (round(microtime(true) - $fStartSQL, 2)) . ' for ' . count($aValuesIRR). ' lines ');

        $fStartXIRR = microtime(true);
        $fXIRR      = $this->calculateIRR($aValuesIRR);
        $this->oLogger->info('Unilend - XIRR Time : ' . (round(microtime(true) - $fStartXIRR, 2)) . ' - Guess : ' . self::IRR_GUESS . ' MAX_INTERATIONS : '. 100);
        $this->oLogger->info('Unilend - Total time : ' . (round(microtime(true) - $fStartSQL, 2)));

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
        return count($oProjectStatusHistory->countProjectStatusChangesOnDate($sDate, $aProjectStatusTriggeringChange)) > 0
                || count($oLendersAccountsStats->getLendersWithLatePaymentsForIRRUsingProjectsLastStatusHistoryMaterialized()) > 0 ;
    }

    public function getLastUnilendIRR()
    {
        /** @var \unilend_stats $oUnilendStats */
        $oUnilendStats = $this->oEntityManager->getRepository('unilend_stats');
        return array_shift($oUnilendStats->select('type_stat = "IRR"', 'added DESC', null, '1'));
    }

}
