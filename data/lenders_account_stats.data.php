<?php

class lenders_account_stats extends lenders_account_stats_crud
{
    const STAT_VALID_NOK = 0;
    const STAT_VALID_OK  = 1;
    const TYPE_STAT_IRR = 'IRR';

    public function __construct($bdd, $params = '')
    {
        parent::lenders_account_stats($bdd, $params);
    }

    public function getLastIRRForLender($iLenderId)
    {
        $sql = '
            SELECT *
            FROM lenders_account_stats
              WHERE id_lender_account = ' . $iLenderId . ' AND type_stat = "' . self::TYPE_STAT_IRR . '"
            ORDER BY date DESC
            LIMIT 1';

        return $this->bdd->fetch_assoc($this->bdd->query($sql));
    }

    /**
     * @param $iLendersAccountId
     * @return array
     */
    public function getValuesForIRR($iLendersAccountId)
    {
        $aValuesIRR      = [];
        $aDatesTimeStamp = [];

        $sql = '
            SELECT
                psh.added AS date,
                -l.amount AS montant
            FROM loans l
            INNER JOIN projects_status_history psh ON l.id_project = psh.id_project
            INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
            WHERE ps.status = ' . \projects_status::REMBOURSEMENT . '
                AND l.id_lender = ' . $iLendersAccountId . '
            GROUP BY l.id_project, l.id_loan

        UNION ALL

            SELECT
                e.date_echeance_reel AS date,
                CASE WHEN e.status_ra = 1 THEN e.capital_rembourse ELSE e.capital_rembourse + e.interets_rembourses END AS montant
            FROM echeanciers e
            WHERE
                e.id_lender = ' . $iLendersAccountId . '
                AND e.status = 1

        UNION ALL

            SELECT
                e.date_echeance AS date,
                e.capital + e.interets AS montant
            FROM echeanciers e
            INNER JOIN projects p ON e.id_project = p.id_project
            WHERE
                e.id_lender = ' . $iLendersAccountId . '
                AND e.status = 0
                AND p.status = ' . \projects_status::REMBOURSEMENT . '

        UNION ALL

            SELECT
                e.date_echeance AS date,
                CASE WHEN e.date_echeance < NOW() THEN "0" ELSE e.capital + e.interets END AS montant
            FROM echeanciers e
            INNER JOIN projects p ON e.id_project = p.id_project
            WHERE
                e.id_lender = ' . $iLendersAccountId . '
                AND e.status = 0
                AND p.status IN (' . implode(',', [\projects_status::PROBLEME, \projects_status::PROBLEME_J_X]) . ')

        UNION ALL

            SELECT
                e.date_echeance AS date,
                CASE WHEN e.date_echeance < NOW() THEN "0" ELSE
                CASE WHEN DATEDIFF(NOW(),
                    (
                        SELECT psh2.added
                        FROM projects_status_history psh2
                        INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                        WHERE
                            ps2.status = ' . \projects_status::PROBLEME . '
                            AND psh2.id_project = e.id_project
                        ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                        LIMIT 1
                    )
                ) > 180 THEN "0" ELSE e.capital + e.interets END
                END AS montant
            FROM echeanciers e
            INNER JOIN projects p ON e.id_project = p.id_project
            WHERE
                e.id_lender = ' . $iLendersAccountId . '
                AND e.status = 0
                AND p.status = ' . \projects_status::RECOUVREMENT . '

        UNION ALL

            SELECT
                e.date_echeance AS date,
                "0" AS montant
            FROM echeanciers e
            INNER JOIN projects p ON e.id_project = p.id_project
            WHERE
                e.id_lender = ' . $iLendersAccountId . '
                AND e.status = 0
                AND p.status IN (' . implode(',', [\projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::DEFAUT]) . ')

        UNION ALL

                 SELECT
                  date_transaction AS date,
                  montant
                FROM transactions
                INNER JOIN lenders_accounts ON transactions.id_client = lenders_accounts.id_client_owner
                WHERE
                lenders_accounts.id_lender_account = ' . $iLendersAccountId . '
                AND type_transaction = ' . \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT;

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

        $aProjectStatusCollectiveProceeding = [
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE,
            \projects_status::DEFAUT
        ];

        $sql = '
            SELECT SUM(e.capital)
            FROM echeanciers e
            INNER JOIN projects p ON e.id_project = p.id_project
            WHERE
                e.id_lender = ' . $iLendersAccountId . '
                AND e.status = 0
                AND (p.status IN (' . implode(',', $aProjectStatusCollectiveProceeding) . ')
                    OR (p.status = ' . \projects_status::RECOUVREMENT . ' AND DATEDIFF(NOW(), e.date_echeance) > 180))';

        $result               = $this->bdd->query($sql);
        $fRemainingDueCapital = ($this->bdd->result($result, 0, 0) / 100);

        if ($iSumOfLoans > 0) {
            $fLossRate = round($fRemainingDueCapital / $iSumOfLoans, 2) * 100;
        } else {
            $fLossRate = null ;
        }

        return $fLossRate;
    }

    public function getLendersWithLatePaymentsForIRR()
    {
        $sQuery =   '
            SELECT e.id_lender
            FROM echeanciers e
            INNER JOIN projects p ON e.id_project = p.id_project
            WHERE
                e.date_echeance < NOW()
                AND (
                    SELECT MAX(las1.date)
                    FROM lenders_account_stats las1
                    WHERE e.id_lender = las1.id_lender_account
                ) < e.date_echeance
                AND e.status = 0
                AND p.status IN (' . implode(',', [\projects_status::PROBLEME, \projects_status::PROBLEME_J_X, \projects_status::RECOUVREMENT]) . ')
            GROUP BY id_lender';

        $aLenderIds = [];
        $rResult   = $this->bdd->query($sQuery);
        while ($aRecord = $this->bdd->fetch_assoc($rResult)) {
            $aLenderIds[] = $aRecord;
        }
        return $aLenderIds;
    }

    public function getAverageIRRofAllLenders()
    {
        $query = '
            SELECT ROUND(AVG(las.value), 2)
            FROM lenders_account_stats las
            INNER JOIN (
                SELECT MAX(id_lenders_accounts_stats) AS id_lenders_accounts_stats
                FROM lenders_account_stats
                WHERE type_stat = "' . self::TYPE_STAT_IRR . '"
                    AND status = ' . self::STAT_VALID_OK . '
                  AND DATE(date) <= NOW()
                GROUP BY id_lender_account
            ) las_max ON las.id_lenders_accounts_stats = las_max.id_lenders_accounts_stats
            WHERE  type_stat = "' . self::TYPE_STAT_IRR . '"
                AND status = ' . self::STAT_VALID_OK . '
                AND DATE(las.date) <= NOW()';
        $statement = $this->bdd->executeQuery($query);

        return $statement->fetchColumn(0);
    }
}
