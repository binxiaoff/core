<?php
/**
 * Created by PhpStorm.
 * User: annabreyer
 * Date: 14/04/2016
 * Time: 15:31
 */

namespace Unilend\Service;

use Unilend\core\Loader;
use Unilend\librairies\ULogger;

/**
 * Class IRRManager
 * @package Unilend\Service
 */
class IRRManager
{
    const IRR_GUESS = 0.1;

    /** @var  ULogger */
    private $oLogger;

    /** @var array */
    private $aConfig;

    public function __construct()
    {
        $this->aConfig = Loader::loadConfig();

    }

    /**
     * @param ULogger $oLogger
     */
    public function setLogger(ULogger $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    public function updateIRRUnilend()
    {
        /** @var \unilend_stats $oUnilendStats */
        $oUnilendStats = Loader::loadData('unilend_stats');

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
        $oLendersAccountStats = Loader::loadData('lenders_account_stats');

        $fStartSQL  = microtime(true);
        $aValuesIRR = $oLendersAccountStats->getValuesForIRRUsingProjectsLastStatusHistoryMaterialized($iLenderId);
        $this->oLogger->addRecord(ULogger::INFO, 'Lender ' . $iLenderId . ' - SQL Time : ' . (round(microtime(true) - $fStartSQL, 2)) . ' for ' . count($aValuesIRR). ' lines ');

        $fStartXIRR = microtime(true);
        $fXIRR = $this->calculateIRR($aValuesIRR);
        $this->oLogger->addRecord(ULogger::INFO, 'Lender ' . $iLenderId . ' - XIRR Time : ' . (round(microtime(true) - $fStartXIRR, 2)) . ' - Guess : ' . self::IRR_GUESS . ' MAX_INTERATIONS : '. 100);
        $this->oLogger->addRecord(ULogger::INFO, 'Lender ' . $iLenderId . ' - Total time : ' . (round(microtime(true) - $fStartSQL, 2)));

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
        $oUnilendStats = Loader::loadData('unilend_stats');

        $this->setLogger(new ULogger('Calculate IRR', $this->aConfig['log_path'][$this->aConfig['env']], 'IRR.' . date('Ymd') . '.log'));

        $fStartSQL  = microtime(true);
        $aValuesIRR = $oUnilendStats->getDataForUnilendIRRUsingProjectsLastStatusMaterialized();
        $this->oLogger->addRecord(ULogger::INFO, 'Unilend - SQL Time : ' . (round(microtime(true) - $fStartSQL, 2)) . ' for ' . count($aValuesIRR). ' lines ');

        $fStartXIRR = microtime(true);
        $fXIRR      = $this->calculateIRR($aValuesIRR);
        $this->oLogger->addRecord(ULogger::INFO, 'Unilend - XIRR Time : ' . (round(microtime(true) - $fStartXIRR, 2)) . ' - Guess : ' . self::IRR_GUESS . ' MAX_INTERATIONS : '. 100);
        $this->oLogger->addRecord(ULogger::INFO, 'Unilend - Total time : ' . (round(microtime(true) - $fStartSQL, 2)));

        return $fXIRR;
    }

    /**
     * @param $sDate
     * @return bool
     */
    public function IRRUnilendNeedsToBeRecalculated($sDate)
    {
        /** @var \lenders_account_stats $oLendersAccountsStats */
        $oLendersAccountsStats = Loader::loadData('lenders_account_stats');
        /** @var \projects_status_history $oProjectStatusHistory */
        $oProjectStatusHistory = Loader::loadData('projects_status_history');
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
        $oUnilendStats = Loader::loadData('unilend_stats');
        return array_shift($oUnilendStats->select('type_stat = "IRR"', 'added DESC', null, '1'));
    }

}
