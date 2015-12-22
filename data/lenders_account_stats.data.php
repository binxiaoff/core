<?php

class lenders_account_stats extends lenders_account_stats_crud
{

    public function __construct($bdd, $params = '')
    {
        parent::lenders_account_stats($bdd, $params);
    }

    public function getLastIRRForLender($iLenderId)
    {

        $sql = 'SELECT *
                    FROM
                        `lenders_account_stats`
                    WHERE
                        id_lender_account = ' . $iLenderId . '
                    ORDER BY
                        tri_date DESC
                    LIMIT
                        1';

        $resultat = $this->bdd->query($sql);

        $result = $this->bdd->fetch_assoc($resultat);

        return $result;

    }

    /**
     * @param int $iLimit number of lender accounts to be selected
     * @return array with lenders
     */
    public function selectLendersForIRR($iLimit)
    {
        $sql = 'SELECT
                    b.id_lender_account,
                    la.added,
                    MAX(las.tri_date) AS last_tri_date
                FROM
                    lenders_accounts la
                    INNER JOIN clients c ON la.id_client_owner = c.id_client
                    INNER JOIN bids b ON b.id_lender_account = la.id_lender_account
                    LEFT JOIN lenders_account_stats las ON la.id_lender_account = las.id_lender_account
                WHERE
                    c.status = 1
                GROUP BY
                    b.id_lender_account
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
        //get loans values as negativ , dates and project status
        $sql = 'SELECT -l.amount AS loan, psh.added AS date
                FROM loans l
                INNER JOIN projects_status_history psh ON l.id_project = psh.id_project
                INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE ps.status = ' . \projects_status::REMBOURSEMENT . '
                AND l.id_lender = ' . $iLendersAccountId . '
                GROUP BY l.id_project,l.id_loan';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_array($result)) {
            $aValuesIRR[] = array($record["date"] => $record["loan"]);

        }
        //get echeancier values
        $sql = 'SELECT
                        CASE
                            WHEN e.status_ra = 1 THEN e.capital
                            ELSE e.montant
                            END AS montant,
                        e.date_echeance_reel AS date_echeance_reel,
                        e.date_echeance AS date_echeance,
                        e.status AS echeance_status,
                            (
                            SELECT ps.status
                            FROM projects_status ps
                                    LEFT JOIN projects_status_history psh ON (
                                    ps.id_project_status = psh.id_project_status)
                                    WHERE psh.id_project = p.id_project
                                    ORDER BY psh.added DESC LIMIT 1) AS project_status
                        FROM echeanciers e
                            LEFT JOIN projects p ON e.id_project = p.id_project
                            INNER JOIN loans l ON e.id_loan = l.id_loan
                        WHERE e.id_lender = ' . $iLendersAccountId;

        $result = $this->bdd->query($sql);

        while ($record = $this->bdd->fetch_array($result)) {

            if (\projects_status::checkStatusPostRepayment($record['project_status'])) {

                if (\projects_status::checkStatusKo($record['project_status']) && 0 == $record["echeance_status"]) {
                    $record["montant"] = 0;
                }

                if ($record["date_echeance_reel"] == "0000-00-00 00:00:00") {
                    $record["date_echeance_reel"] = $record["date_echeance"];
                }

                $aValuesIRR[] = array($record["date_echeance_reel"] => $record["montant"]);
            }
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
            $oLoggerIRR    = new ULogger('Calculate IRR', $this->logPath, 'IRR.log');
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