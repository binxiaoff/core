<?php
use Unilend\librairies\ULogger;

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
     * @param int $iLendersAccount
     *
     * @return array with dates and values of loans and dues sorted by date (impacts result of calculation)
     */
    public function getValuesForIRR($iLendersAccountId)
    {
        $aValuesIRR      = array();
        $aDatesTimeStamp = array();

        $sql = 'SELECT psh.added AS date,
                       -l.amount AS montant
                FROM loans l
                    INNER JOIN projects_status_history psh ON l.id_project = psh.id_project
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE ps.status = ' . \projects_status::REMBOURSEMENT . '
                    AND l.id_lender = ' . $iLendersAccountId . '
                GROUP BY l.id_project,l.id_loan

                UNION ALL

                SELECT
                    e.date_echeance_reel AS date,
                    CASE WHEN e.status_ra = 1 THEN e.capital ELSE e.capital + e.interets END AS montant
                FROM
                    echeanciers e
                    INNER JOIN projects_last_status_history_materialized plshm ON e.id_project = plshm.id_project
                    INNER JOIN projects_status_history psh ON plshm.id_project_status_history = psh.id_project_status_history
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE
                    e.id_lender = ' . $iLendersAccountId . '
                    AND e.status = 1

                UNION ALL

                SELECT
                    e.date_echeance AS date,
                    e.capital + e.interets AS montant
                FROM
                    echeanciers e
                    INNER JOIN projects_last_status_history_materialized plshm ON e.id_project = plshm.id_project
                    INNER JOIN projects_status_history psh ON plshm.id_project_status_history = psh.id_project_status_history
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE
                    e.id_lender = ' . $iLendersAccountId . '
                    AND e.status = 0
                    AND ps.status = ' . \projects_status::REMBOURSEMENT . '

                UNION ALL

                SELECT
                    e.date_echeance AS date,
                    CASE WHEN e.date_echeance < NOW() THEN "0" ELSE e.capital + e.interets END AS montant
                FROM
                    echeanciers e
                    INNER JOIN projects_last_status_history_materialized plshm ON e.id_project = plshm.id_project
                    INNER JOIN projects_status_history psh ON plshm.id_project_status_history = psh.id_project_status_history
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE
                    e.id_lender = ' . $iLendersAccountId . '
                    AND e.status = 0
                    AND ps.status IN (' . implode(',', array(\projects_status::PROBLEME, \projects_status::PROBLEME_J_X)) . ')

                UNION ALL

                SELECT
                    e.date_echeance AS date,
                    CASE WHEN e.date_echeance < NOW() THEN "0" ELSE
                        CASE WHEN DATEDIFF(NOW(),
                        (SELECT psh2.added
                            FROM projects_status_history psh2
                                INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                            WHERE
                                ps2.status = ' . \projects_status::PROBLEME . '
                                AND psh2.id_project = e.id_project
                            ORDER BY psh2.id_project_status_history DESC
                            LIMIT 1)
                    ) > 180 THEN "0" ELSE e.capital + e.interets END
                    END AS montant
                FROM
                    echeanciers e
                    INNER JOIN projects_last_status_history_materialized plshm ON e.id_project = plshm.id_project
                    INNER JOIN projects_status_history psh ON plshm.id_project_status_history = psh.id_project_status_history
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE
                    e.id_lender = ' . $iLendersAccountId . '
                    AND e.status = 0
                    AND ps.status = ' . \projects_status::RECOUVREMENT . '

                UNION ALL

                SELECT
                    e.date_echeance AS date,
                    "0" AS montant
                FROM
                    echeanciers e
                    INNER JOIN projects_last_status_history_materialized plshm ON e.id_project = plshm.id_project
                    INNER JOIN projects_status_history psh ON plshm.id_project_status_history = psh.id_project_status_history
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
        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_array($result)) {
            $aValuesIRR[]      = array($record['date'] => $record['montant']);
            $aDatesTimeStamp[] = strtotime($record['date']);
        }
        array_multisort($aDatesTimeStamp, SORT_ASC, SORT_NUMERIC, $aValuesIRR);
        return $aValuesIRR;
    }



    public function getLossRate($iLendersAccountId, lenders_accounts $oLendersAccounts)
    {
        $iSumOfLoans = $oLendersAccounts->sumLoansOfProjectsInRepayment($iLendersAccountId);

        $aProjectStatusCollectiveProceeding = array(
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE,
            \projects_status::DEFAUT
        );

        $sql = 'SELECT
                    SUM(e.capital)
                FROM
                    echeanciers e
                    INNER JOIN projects p ON e.id_project = p.id_project
                    INNER JOIN projects_last_status_history plsh ON p.id_project = plsh.id_project
                    INNER JOIN projects_status_history psh ON plsh.id_project_status_history = psh.id_project_status_history
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE
                    e.id_lender = ' . $iLendersAccountId . '
                    AND e.status = 0
                    AND (ps.status IN (' . implode(',', $aProjectStatusCollectiveProceeding) . ')
                        OR (ps.status = ' . \projects_status::RECOUVREMENT . ' AND DATEDIFF(NOW(), e.date_echeance) > 180))';

        $result               = $this->bdd->query($sql);
        $fRemainingDueCapital = ($this->bdd->result($result, 0, 0) / 100);

        if ($iSumOfLoans > 0) {
            $fLossRate = round($fRemainingDueCapital / $iSumOfLoans, 2) * 100;
        } else {
            $fLossRate = null ;
        }

        return $fLossRate;
    }

    /**
     * @param array|null $aProjectStatus
     * @return array of LenderIDs
     */
    public function getLendersWithLatePaymentsForIRR(array $aProjectStatus = null)
    {
        $sProjectStatus = '';
        if (false === is_null($aProjectStatus)) {
            $sProjectStatus = 'AND ps.status IN (' . implode(',', $aProjectStatus) . ')';
        }

        $sQuery =   'SELECT
                        e.id_lender
                    FROM
                        echeanciers e
                        INNER JOIN projects_last_status_history_materialized plshm ON e.id_project = plshm.id_project
                        INNER JOIN projects_status_history psh ON plshm.id_project_status_history = psh.id_project_status_history
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE
                        e.date_echeance < NOW()
                        AND (
                            SELECT
                                MAX(las1.tri_date)
                            FROM
                                lenders_account_stats las1
                            WHERE
                                e.id_lender = las1.id_lender_account
                        ) < e.date_echeance
                        AND e.status = 0 ' . $sProjectStatus . '
                    GROUP BY
                        id_lender';

        $aLenderIds = array();
        $rResult   = $this->bdd->query($sQuery);
        while ($aRecord = $this->bdd->fetch_assoc($rResult)) {
            $aLenderIds[] = $aRecord;
        }
        return $aLenderIds;
    }
}
