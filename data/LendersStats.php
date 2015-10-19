<?php

namespace Unilend\data;

use Unilend\core\Bootstrap;
use Unilend\librairies\ULogger;

/**
 * Require project status for class lenders_accounts
 */
require_once __DIR__ . '/../data/crud/projects_status.crud.php';
require_once __DIR__ . '/../data/projects_status.data.php';

class LendersStats
{
    /**
     * @var Boostrap
     */
    private $oBoostrap;

    /**
     * @var bdd
     */
    private $oDatabase;

    /**
     * @var lenders_account
     */
    private $oLenders;

    /**
     * @param object $oBootstrap Unilend\core\Bootstrap
     */
    public function __construct(Bootstrap $oBootstrap)
    {
        $this->oBoostrap = $oBootstrap;
        $this->oDatabase = $this->oBoostrap->setDatabase()->getDatabase();
        $this->oLenders  = $this->oBoostrap->setLenders()->getLenders();
    }

    public function getTRI()
    {
        $sQuery = "SELECT la.id_lender_account
                    FROM lenders_accounts la
                    LEFT JOIN clients c on la.id_client_owner = c.id_client
                    WHERE (c.status_pre_emp = 1 or c.status_pre_emp = 2)";

        $rSql = $this->oDatabase->query($sQuery);
        if (false !== $rSql) {
            $oFinancial = new \PHPExcel_Calculation_Financial();
            while ($aRow = $this->oDatabase->fetch_assoc($rSql)) {
                $aValuesTRI = $this->oLenders->getValuesforTRI($aRow['id_lender_account']);
                if (false === empty($aValuesTRI)) {
                    $iTimeStart = microtime(true);
                    $fXIRR      = round($oFinancial->XIRR(array_values($aValuesTRI), array_keys($aValuesTRI)) * 100, 2);
                    $this->oBoostrap->setLogger($aRow['id_lender_account'], 'calculTRI.log');
                    $this->oBoostrap->getLogger()->addRecord(ULogger::INFO, 'Temps calcul TRI : ' . round(microtime(true) - $iTimeStart, 2));
                    $this->insertStats($aRow['id_lender_account'], $fXIRR);
                }
            }

            $this->oDatabase->free_result($rSql);
        }
    }

    private function insertStats($iIdLender, $fTRI)
    {
        $sQuery = "INSERT INTO lenders_accounts_stats
                    VALUES (null, $iIdLender, $fTRI, NOW())";
        $this->oDatabase->query($sQuery);
    }
}