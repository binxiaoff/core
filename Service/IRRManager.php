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

    public function updateIRRUnilend()
    {
        /** @var \unilend_stats $oUnilendStats */
        $oUnilendStats = Loader::loadData('unilend_stats');

        $aLastUnilendIRR = $oUnilendStats->select('type_stat = "IRR"', 'added DESC', '1');
        $oLastIRRDate    = new \DateTime($aLastUnilendIRR['added']);
        $oNow            = new \DateTime('NOW');
        $oDateDifference = $oNow->diff($oLastIRRDate);

        $fIRRUnilend = $this->calculateIRRUnilend();

        if ($oDateDifference->d = 0) {
            $oUnilendStats->get($aLastUnilendIRR['id_unilend_stat'], 'id_unilend_stat');
            $oUnilendStats->value = $fIRRUnilend;
            $oUnilendStats->update();
        } else {
            $oUnilendStats->value = $fIRRUnilend;
            $oUnilendStats->create();
        }
    }


    /**
     * @param $aValuesIRR
     * @param float $fGuess
     * @return string
     * @throws \Exception
     */
    public function calculateIRR($aValuesIRR)
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
     * @param ULogger $oLoggerIRR
     * @param float $fGuess
     * @return string
     * @throws \Exception
     */
    public function calculateIRRForLender($iLenderId, ULogger $oLoggerIRR)
    {
        /** @var \lenders_account_stats $aLendersAccountStats */
        $oLendersAccountStats = Loader::loadData('lenders_account_stats');

        $fStartSQL  = microtime(true);
        $aValuesIRR = $oLendersAccountStats->getValuesForIRR($iLenderId);
        $oLoggerIRR->addRecord(ULogger::INFO, 'Lender ' . $iLenderId . ' - SQL Time : ' . (round(microtime(true) - $fStartSQL, 2)) . ' for ' . count($aValuesIRR). ' lines ');

        $fStartXIRR = microtime(true);
        $fXIRR = $this->calculateIRR($aValuesIRR);
        $oLoggerIRR->addRecord(ULogger::INFO, 'Lender ' . $iLenderId . ' - XIRR Time : ' . (round(microtime(true) - $fStartXIRR, 2)) . ' - Guess : ' . self::IRR_GUESS . ' MAX_INTERATIONS : '. 100);
        $oLoggerIRR->addRecord(ULogger::INFO, 'Lender ' . $iLenderId . ' - Total time : ' . (round(microtime(true) - $fStartSQL, 2)));

        return $fXIRR;
    }

    public function calculateIRRUnilend(ULogger $oLoggerIRR = null)
    {
        /** @var \unilend_stats $oUnilendStats */
        $oUnilendStats = Loader::loadData('unilend_stats');

        $fStartSQL  = microtime(true);
        $aValuesIRR = $oUnilendStats->getValuesForIRR();
        $oLoggerIRR->addRecord(ULogger::INFO, 'Unilend - SQL Time : ' . (round(microtime(true) - $fStartSQL, 2)) . ' for ' . count($aValuesIRR). ' lines ');

        $fStartXIRR = microtime(true);
        $fXIRR = $this->calculateIRR($aValuesIRR);
        $oLoggerIRR->addRecord(ULogger::INFO, 'Unilend - XIRR Time : ' . (round(microtime(true) - $fStartXIRR, 2)) . ' - Guess : ' . self::IRR_GUESS . ' MAX_INTERATIONS : '. 100);
        $oLoggerIRR->addRecord(ULogger::INFO, 'Unilend - Total time : ' . (round(microtime(true) - $fStartSQL, 2)));

        return $fXIRR;
    }





}
