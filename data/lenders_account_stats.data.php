<?php

class lenders_account_stats extends lenders_account_stats_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::lenders_account_stats($bdd, $params);
    }

    public function getLastIRRForLender($iLenderId)
    {
        $sql = '
            SELECT *
            FROM lenders_account_stats
            WHERE id_lender_account = ' . $iLenderId . '
            ORDER BY tri_date DESC
            LIMIT 1';

        return $this->bdd->fetch_assoc($this->bdd->query($sql));
    }

    /**
     * @param int $iLimit number of lender accounts to be selected
     * @return array with lenders
     */
    public function selectLendersForIRR($iLimit)
    {
        $sql = 'SELECT
                    l.id_lender,
                    la.added,
                    MAX(las.tri_date) AS last_tri_date
                FROM
                    lenders_accounts la
                    INNER JOIN clients c ON la.id_client_owner = c.id_client
                    INNER JOIN loans l ON l.id_lender = la.id_lender_account
                    LEFT JOIN lenders_account_stats las ON la.id_lender_account = las.id_lender_account
                WHERE
                    c.status = 1
                GROUP BY
                    l.id_lender
                ORDER BY
                    last_tri_date ASC,
                    la.added DESC
                LIMIT ' . $iLimit;
        $result   = $this->bdd->query($sql);
        $aLenders = array();
        while ($record = $this->bdd->fetch_array($result)) {
            $aLenders[] = $record;
        }
        return $aLenders;
    }

    /**
     * @param int $iLendersAccountId unique identifier of the lender
     * @return array with dates and values of loans and dues
     * @throws Exception when there is no id_lender_account
     */
    private function getValuesForIRR($iLendersAccountId)
    {
        $aValuesIRR = array();

        $sSqlLoans = 'SELECT  -l.amount AS loan,
                        psh.added AS date
                FROM loans l
                    INNER JOIN projects_status_history psh ON l.id_project = psh.id_project
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE ps.status = ' . \projects_status::REMBOURSEMENT . '
                    AND l.id_lender = ' . $iLendersAccountId . '
                GROUP BY l.id_project,l.id_loan';

        $result = $this->bdd->query($sSqlLoans);
        while ($record = $this->bdd->fetch_array($result)) {
            $aValuesIRR[] = array($record["date"] => $record["loan"]);

        }

        $sSqlPayments = 'SELECT
                    e.date_echeance_reel AS date,
                    CASE WHEN e.status_ra = 1 THEN e.capital ELSE e.capital + e.interets END AS montant
                FROM
                    echeanciers e
                    INNER JOIN projects p ON e.id_project = p.id_project
                    INNER JOIN projects_last_status_history plsh ON p.id_project = plsh.id_project
                    INNER JOIN projects_status_history psh ON plsh.id_project_status_history = psh.id_project_status_history
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE
                    e.id_lender = ' . $iLendersAccountId . '
                    AND e.status = 1

                UNION

                SELECT
                    e.date_echeance AS date,
                    CASE WHEN e.date_echeance < NOW() THEN "0" ELSE e.capital + e.interets END AS montant
                FROM
                    echeanciers e
                    INNER JOIN projects p ON e.id_project = p.id_project
                    INNER JOIN projects_last_status_history plsh ON p.id_project = plsh.id_project
                    INNER JOIN projects_status_history psh ON plsh.id_project_status_history = psh.id_project_status_history
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE
                    e.id_lender = ' . $iLendersAccountId . '
                    AND e.status = 0
                    AND ps.status IN (' . implode(',', array(\projects_status::PROBLEME, \projects_status::PROBLEME_J_X)) . ')

                UNION

                SELECT
                    e.date_echeance AS date,
                    CASE WHEN e.date_echeance < NOW() THEN erp.capital + erp.interets ELSE e.capital + e.interets + erp.capital + erp.interets END AS montant
                FROM
                    echeanciers e
                    INNER JOIN projects p ON e.id_project = p.id_project
                    INNER JOIN projects_last_status_history plsh ON p.id_project = plsh.id_project
                    INNER JOIN projects_status_history psh ON plsh.id_project_status_history = psh.id_project_status_history
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                        INNER JOIN echeanciers_recouvrements_prorata erp ON e.id_echeancier = erp.id_echeancier
                WHERE
                    e.id_lender = ' . $iLendersAccountId . '
                    AND e.status = 0
                    AND ps.status = ' . \projects_status::RECOUVREMENT . '
                    AND DATEDIFF(e.date_echeance, NOW()) < 180

                UNION

                SELECT
                    e.date_echeance AS date,
                    erp.capital + erp.interets AS montant
                FROM
                    echeanciers e
                    INNER JOIN projects p ON e.id_project = p.id_project
                    INNER JOIN projects_last_status_history plsh ON p.id_project = plsh.id_project
                    INNER JOIN projects_status_history psh ON plsh.id_project_status_history = psh.id_project_status_history
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    LEFT JOIN echeanciers_recouvrements_prorata erp ON e.id_echeancier = erp.id_echeancier
                WHERE
                    e.id_lender = ' . $iLendersAccountId . '
                    AND e.status = 0
                    AND ps.status = ' . \projects_status::RECOUVREMENT . '
                    AND DATEDIFF(e.date_echeance, NOW()) >= 180

                UNION

                SELECT
                    e.date_echeance AS date,
                    "0" AS montant
                FROM
                    echeanciers e
                    INNER JOIN projects p ON e.id_project = p.id_project
                    INNER JOIN projects_last_status_history plsh ON p.id_project = plsh.id_project
                    INNER JOIN projects_status_history psh ON plsh.id_project_status_history = psh.id_project_status_history
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE
                    e.id_lender = ' . $iLendersAccountId . '
                    AND e.status = 0
                    AND ps.status IN (' . implode(',', array(
                \projects_status::PROCEDURE_SAUVEGARDE,
                \projects_status::REDRESSEMENT_JUDICIAIRE,
                \projects_status::LIQUIDATION_JUDICIAIRE,
                \projects_status::DEFAUT
            )) . ')';

        $result = $this->bdd->query($sSqlPayments);

        while ($record = $this->bdd->fetch_array($result)) {
                $aValuesIRR[] = array($record["date_echeance_reel"] => $record["montant"]);
        }
        return $aValuesIRR;
    }

    /**
     * @param int $iLendersAccountId unique identifier of the lender for who the IRR should be calculated
     * @return float with IRR value
     * @throws Exception when not values are available to be used in the calculation,
     * when the result is not in the accepted range
     */
    public function calculateIRR($iLendersAccountId)
    {
        try {
            $aValuesIRR = $this->getValuesForIRR($iLendersAccountId);
        } catch (Exception $e){
            $oLoggerIRR = new ULogger('Calculate IRR', $this->logPath, 'IRR.log');
            $oLoggerIRR->addRecord(ULogger::WARNING, 'Caught Exception: '.$e->getMessage(). ' '. $e->getTraceAsString());
        }

        foreach ($aValuesIRR as $aValues) {
            foreach ($aValues as $date => $value) {
                $aDates[] = $date;
                $aSums[]  = $value;
            }
        }

        $oFinancial = new \PHPExcel_Calculation_Financial();
        $fXIRR      = round($oFinancial->XIRR($aSums, $aDates) * 100, 2);

        if (abs($fXIRR) > 100) {
            throw new Exception('IRR not in range for '.$iLendersAccountId. ' IRR : '. $fXIRR);
        }
        return $fXIRR;
    }




}
