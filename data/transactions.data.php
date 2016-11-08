<?php
// **************************************************************************************************** //
// ***************************************    ASPARTAM    ********************************************* //
// **************************************************************************************************** //
//
// Copyright (c) 2008-2011, equinoa
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
// associated documentation files (the "Software"), to deal in the Software without restriction,
// including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
// subject to the following conditions:
// The above copyright notice and this permission notice shall be included in all copies
// or substantial portions of the Software.
// The Software is provided "as is", without warranty of any kind, express or implied, including but
// not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.
// In no event shall the authors or copyright holders equinoa be liable for any claim,
// damages or other liability, whether in an action of contract, tort or otherwise, arising from,
// out of or in connection with the software or the use or other dealings in the Software.
// Except as contained in this notice, the name of equinoa shall not be used in advertising
// or otherwise to promote the sale, use or other dealings in this Software without
// prior written authorization from equinoa.
//
//  Version : 2.4.0
//  Date : 21/03/2011
//  Coupable : CM
//
// **************************************************************************************************** //

class transactions extends transactions_crud
{
    const PAYMENT_TYPE_VISA       = 0;
    const PAYMENT_TYPE_MASTERCARD = 3;
    const PAYMENT_TYPE_AUTO       = 1;
    const PAYMENT_TYPE_AMEX       = 2;

    const PAYMENT_STATUS__NOK = 0;
    const PAYMENT_STATUS_OK   = 1;

    const STATUS_PENDING  = 0;
    const STATUS_VALID    = 1;
    const STATUS_CANCELED = 3;

    public function __construct($bdd, $params = '')
    {
        parent::transactions($bdd, $params);
        \Unilend\core\Loader::loadData('transactions_types');
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `transactions`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $result = $this->bdd->query('SELECT COUNT(*) FROM `transactions` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_transaction')
    {
        $result = $this->bdd->query('SELECT * FROM `transactions` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function sum($where = '', $champ)
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT SUM(' . $champ . ') FROM `transactions` ' . $where;

        $result = $this->bdd->query($sql);
        $return = (int) ($this->bdd->result($result, 0, 0));

        return $return;
    }

    public function getSolde($id_client)
    {
        $sql = '
            SELECT SUM(montant) AS solde
            FROM transactions
            WHERE etat = 1
                AND status = 1
                AND id_client = ' . $id_client;

        $result = $this->bdd->query($sql);
        $solde  = $this->bdd->result($result);
        if ($solde == '') {
            $solde = 0;
        } else {
            $solde = ($solde / 100);
        }
        return $solde;
    }

    // solde jusqu'a une certaine date (solde a une date precise)
    public function getSoldeDateLimite($id_client, $dateLimite)
    {
        $sql = '
            SELECT SUM(montant) AS solde
            FROM transactions
            WHERE etat = 1
                AND status = 1
                AND id_client = ' . $id_client . '
                AND type_transaction NOT IN (' . implode(', ', array(\transactions_types::TYPE_BORROWER_REPAYMENT, \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT, \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION)) . ')
                AND DATE(added) <= "' . $dateLimite . '"';

        $result = $this->bdd->query($sql);
        $solde  = $this->bdd->result($result);
        if ($solde == '') {
            $solde = 0;
        } else {
            $solde = ($solde / 100);
        }
        return $solde;
    }

    public function getRepaymentTransactionsAmount($iEcheanceId)
    {
        if (false == empty($iEcheanceId)) {
            $sWhere = 'type_transaction IN (' . \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL . ', ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . ')
            AND id_echeancier = ' . $iEcheanceId;
            return $this->sum($sWhere, 'montant');
        }
    }

    public function getOperationsForIndexing($transactionTypeLabel,  $lastIndexedOperationDate, $clientId)
    {
        $sql = '
        (
          SELECT
            DISTINCT
            t.id_transaction,
            t.date_transaction,
            t.id_client,
            t.id_echeancier,
            t.type_transaction,
            0 AS capital,
            0 AS interests,
            CASE ';
        foreach ($transactionTypeLabel as $typeId => $label) {
            if ($typeId == \transactions_types::TYPE_LENDER_LOAN) {
                foreach ($label as $subtypeId => $offre) {
                    // offre en cours
                    if ($subtypeId == 1) {
                        $sql .= ' WHEN t.type_transaction = ' . $typeId . ' AND t.montant <= 0 THEN "' . $offre . '"';
                    } // offre rejeté
                    elseif ($subtypeId == 2) {
                        $sql .= ' WHEN t.type_transaction = ' . $typeId . ' AND t.montant > 0 THEN "' . $offre . '"';
                    }
                }
            } else {
                $sql .= '
                    WHEN t.type_transaction = ' . $typeId . ' THEN "' . $label . '"';
            }
        }
        $sql .='
            ELSE ""
            END AS type_transaction_alpha,
            CASE
            WHEN b.id_project IS NULL THEN CASE WHEN b2.id_project IS NULL THEN t.id_project ELSE b2.id_project END
            ELSE b.id_project END AS id_project,
            date_transaction AS date_tri,
            (
              SELECT SUM(t2.montant)
              FROM transactions t2
              WHERE t2.etat = 1 AND t2.status = 1 AND t2.id_client = t.id_client AND t2.type_transaction NOT IN (' . implode(', ', array(\transactions_types::TYPE_BORROWER_REPAYMENT, \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT, \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION)) . ') AND (t2.date_transaction < t.date_transaction OR t2.date_transaction = t.date_transaction AND t2.id_transaction <= t.id_transaction)
            ) AS solde,
            CASE t.type_transaction
            WHEN ' . \transactions_types::TYPE_LENDER_LOAN . ' THEN (
              SELECT title
              FROM projects
              WHERE id_project = t.id_project
            )
            WHEN ' . \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT . ' THEN (
              SELECT title
              FROM projects
              WHERE id_project = t.id_project
            )
            WHEN ' . \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT . ' THEN (
              SELECT title
              FROM projects
              WHERE id_project = t.id_project
            )
            ELSE ""
            END AS title,
            CASE t.type_transaction
            WHEN ' . \transactions_types::TYPE_LENDER_LOAN . ' THEN 0
            WHEN ' . \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT . ' THEN (
              SELECT e.id_loan
              FROM echeanciers e
              WHERE e.id_project = t.id_project AND w.id_lender = e.id_lender
              LIMIT 1
            )
            ELSE ""
            END AS bdc,
            t.montant AS amount_operation,
            0 AS tax_amount
          FROM transactions t
            LEFT JOIN wallets_lines w ON t.id_transaction = w.id_transaction
            LEFT JOIN  bids b ON w.id_wallet_line = b.id_lender_wallet_line
            LEFT JOIN bids b2 ON t.id_bid_remb = b2.id_bid
          WHERE t.date_transaction >= "' . $lastIndexedOperationDate . '"
                AND t.type_transaction IN (' . implode(',', array_keys($transactionTypeLabel)) . ')
                AND t.status = 1
                AND t.etat = 1
                AND t.id_client = ' . $clientId . '
        ) UNION ALL (
          SELECT
            t.id_transaction,
            t.date_transaction,
            t.id_client,
            t.id_echeancier,
            t.type_transaction,
            0 AS capital,
            0 AS interests,
            "' . $transactionTypeLabel[\transactions_types::TYPE_LENDER_LOAN][3] . '" AS type_transaction_alpha,
            lo.id_project AS id_project,
            psh.added AS date_tri,
            (
              SELECT SUM(t2.montant)
              FROM transactions t2
              WHERE t2.etat = 1 AND t2.status = 1 AND t2.id_client = t.id_client AND t2.type_transaction NOT IN (' . implode(', ', array(\transactions_types::TYPE_BORROWER_REPAYMENT, \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT, \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION)) . ') AND (t2.date_transaction < date_tri OR t2.date_transaction = date_tri AND t2.id_transaction <= t.id_transaction)
            ) AS solde,
            p.title AS title,
            lo.id_loan AS bdc,
            lo.amount AS amount_operation,
            0 AS tax_amount
          FROM loans lo INNER JOIN accepted_bids ab ON ab.id_loan = lo.id_loan
            INNER JOIN bids b ON ab.id_bid = b.id_bid
            INNER JOIN wallets_lines w ON w.id_wallet_line = b.id_lender_wallet_line
            INNER JOIN transactions t ON t.id_transaction = w.id_transaction
            INNER JOIN projects p ON p.id_project = lo.id_project
            INNER JOIN projects_status_history psh ON psh.id_project = lo.id_project
          WHERE lo.status = 0
                AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_LOAN . '
                AND t.status = 1
                AND t.etat = 1
                AND t.id_client = ' . $clientId . '
                AND psh.id_project_status_history = (
            SELECT MIN(id_project_status_history)
            FROM projects_status_history psh1
            WHERE psh1.id_project = lo.id_project AND psh1.id_project_status = 8
          )
          GROUP BY lo.id_loan
        ) UNION ALL (
          SELECT
            t.id_transaction,
            t.date_transaction,
            t.id_client,
            t.id_echeancier,
            5 AS type_transaction,
            t.montant as capital,
            IFNULL(interests.montant, 0) AS interests,
            "Remboursement" AS type_transaction_alpha,
            p.id_project AS id_project,
            t.date_transaction AS date_tri,
            (
              SELECT SUM(t2.montant) + (SELECT SUM(montant) FROM transactions WHERE id_echeancier = t.id_echeancier)
              FROM transactions t2
              WHERE t2.etat = 1 AND t2.status = 1 AND t2.id_client = t.id_client AND t2.type_transaction NOT IN (' . implode(', ', array(\transactions_types::TYPE_BORROWER_REPAYMENT, \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT, \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION)) . ') AND (t2.date_transaction < date_tri OR t2.date_transaction = date_tri AND t2.id_transaction < t.id_transaction)
            ) AS solde,
            p.title AS title,
            (
              SELECT e.id_loan
              FROM echeanciers e
              WHERE e.id_echeancier = t.id_echeancier
            ) AS bdc,
            0 AS amount_operation,
            IFNULL((SELECT SUM(amount) FROM tax WHERE id_transaction IN (SELECT id_transaction FROM transactions WHERE id_echeancier = t.id_echeancier)), 0) AS tax_amount
          FROM transactions t
            INNER JOIN echeanciers e ON e.id_echeancier = t.id_echeancier
            INNER JOIN projects p ON p.id_project = e.id_project
            LEFT JOIN transactions interests ON t.id_echeancier = interests.id_echeancier AND interests.type_transaction = 28
          WHERE t.date_transaction >= "' . $lastIndexedOperationDate . '"
                AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL . '
                AND t.status = 1
                AND t.etat = 1
                AND t.id_client = ' . $clientId . '
        )
        ORDER BY date_tri DESC';

        $this->bdd->query('SET SQL_BIG_SELECTS = 1');  //Set it before your main query

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    /**
     * @param array $transactionTypes
     * @param \DateTime $date
     * @return array
     * @throws Exception
     */
    public function getDailyState(array $transactionTypes, \DateTime $date)
    {
        $sql = '
            SELECT
                type_transaction,
                ROUND(SUM(t.montant) / 100, 2) AS montant,
                ROUND(SUM(t.montant_unilend) / 100, 2) AS montant_unilend,
                ROUND(SUM(t.montant_etat) / 100, 2) AS montant_etat,
                DATE(t.date_transaction) AS jour
            FROM transactions t
            WHERE LEFT(t.added, 7) = :transaction_date
                AND t.etat = 1
                AND t.status = 1
                AND t.type_transaction IN(:transaction_type)
            GROUP BY t.type_transaction, DATE(t.date_transaction)
        ';
        $data = $this->bdd->executeQuery($sql,
            ['transaction_type' => $transactionTypes, 'transaction_date' => $date->format('Y-m')],
            ['transaction_type' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY, 'transaction_date' => \PDO::PARAM_STR])->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];
        foreach ($data as $row) {
            $result[$row['type_transaction']][$row['jour']] = [
                'montant'         => $row['montant'],
                'montant_unilend' => $row['montant_unilend'],
                'montant_etat'    => $row['montant_etat'],
            ];
        }
        return $result;
    }

    /**
     * @param DateTime $date
     * @return array
     * @throws Exception
     */
    public function getDailyWelcomeOffer(\DateTime $date)
    {
        $sql = '
            SELECT
                ROUND(SUM(t.montant) / 100, 2) AS montant,
                ROUND(SUM(t.montant_unilend) / 100, 2) AS montant_unilend,
                ROUND(SUM(t.montant_etat) / 100, 2) AS montant_etat,
                DATE(t.date_transaction) AS jour
            FROM transactions t
            INNER JOIN lenders_accounts l ON t.id_client = l.id_client_owner
            WHERE LEFT(t.added, 7) = :transaction_date
                AND t.etat = 1
                AND t.status = 1
                AND t.type_transaction = :transaction_type
                AND l.type_transfert = 2
            GROUP BY DATE(t.date_transaction)
        ';
        $data = $this->bdd->executeQuery($sql,
            ['transaction_type' => \transactions_types::TYPE_LENDER_SUBSCRIPTION, 'transaction_date' => $date->format('Y-m')],
            ['transaction_type' => \PDO::PARAM_INT, 'transaction_date' => \PDO::PARAM_STR])->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];

        foreach ($data as $row) {
            $result[$row['jour']]['montant']         = $row['montant'];
            $result[$row['jour']]['montant_unilend'] = $row['montant_unilend'];
            $result[$row['jour']]['montant_etat']    = $row['montant_etat'];
        }
        return $result;
    }

    /**
     * @param string $startDate yyyy-mm-dd H:i:s date formated
     * @param string $endDate yyyy-mm-dd H:i:s date formated
     * @return int
     */
    public function getInterestsAmount($startDate, $endDate)
    {
        $sql = '
            SELECT SUM(t.montant) as interests
            FROM transactions t
            WHERE
              t.type_transaction = :transaction_type
              AND t.date_transaction BETWEEN :start_date AND :end_date
        ';
        return $this->bdd->executeQuery($sql,
            ['transaction_type' => \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS, 'start_date' => $startDate, 'end_date' => $endDate],
            ['transaction_type' => \PDO::PARAM_INT, 'start_date' => \PDO::PARAM_STR, 'end_date' => \PDO::PARAM_STR])->fetchColumn(0);
    }

    public function getBorrowerRecoveryPaymentsOnHealthyProjectsByCohort()
    {
        $query = 'SELECT
                      SUM(montant / 100) AS amount,
                      (
                        SELECT
                          CASE LEFT(projects_status_history.added, 4)
                            WHEN 2013 THEN "2013-2014"
                            WHEN 2014 THEN "2013-2014"
                            ELSE LEFT(projects_status_history.added, 4)
                          END AS date_range
                        FROM projects_status_history
                        INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE  projects_status.status = '. \projects_status::REMBOURSEMENT .'
                          AND transactions.id_project = projects_status_history.id_project
                        ORDER BY id_project_status_history ASC LIMIT 1
                      ) AS cohort
                    FROM transactions
                      INNER JOIN projects ON transactions.id_project = projects.id_project
                    WHERE transactions.type_transaction = ' . \transactions_types::TYPE_RECOVERY_BANK_TRANSFER . '
                    AND IF(
                            projects.status IN ('. implode(',', [\projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::DEFAUT]).')
                            OR (projects.status IN ('. implode(',', [\projects_status::PROBLEME, \projects_status::PROBLEME_J_X, \projects_status::RECOUVREMENT]).')
                                AND DATEDIFF(NOW(), (
                                                    SELECT psh2.added
                                                    FROM projects_status_history psh2
                                                      INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                                                    WHERE ps2.status = ' . \projects_status::PROBLEME . '
                                                      AND psh2.id_project = transactions.id_project
                                                    ORDER BY psh2.id_project_status_history DESC
                                                    LIMIT 1)) > 180), TRUE, FALSE) = FALSE
                    GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBorrowerRecoveryPaymentsOnProblematicProjectsByCohort()
    {
        $query = 'SELECT
                      SUM(montant / 100) AS amount,
                      (
                        SELECT
                          CASE LEFT(projects_status_history.added, 4)
                            WHEN 2013 THEN "2013-2014"
                            WHEN 2014 THEN "2013-2014"
                            ELSE LEFT(projects_status_history.added, 4)
                          END AS date_range
                        FROM projects_status_history
                        INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE  projects_status.status = '. \projects_status::REMBOURSEMENT .'
                          AND transactions.id_project = projects_status_history.id_project
                        ORDER BY id_project_status_history ASC LIMIT 1
                      ) AS cohort
                    FROM transactions
                      INNER JOIN projects ON transactions.id_project = projects.id_project
                    WHERE transactions.type_transaction = ' . \transactions_types::TYPE_RECOVERY_BANK_TRANSFER . '
                        AND IF(
                            projects.status IN ('. implode(',', [\projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::PROCEDURE_SAUVEGARDE, \projects_status::DEFAUT]).')
                            OR (projects.status IN ('. implode(',', [\projects_status::PROBLEME, \projects_status::PROBLEME_J_X, \projects_status::RECOUVREMENT]).')
                                AND DATEDIFF(NOW(), (
                                                    SELECT psh2.added
                                                    FROM projects_status_history psh2
                                                      INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                                                    WHERE ps2.status = ' . \projects_status::PROBLEME . '
                                                      AND psh2.id_project = transactions.id_project
                                                    ORDER BY psh2.id_project_status_history DESC
                                                    LIMIT 1)) > 180), TRUE, FALSE) = TRUE
                    GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param lenders_accounts $lender
     * @return float
     */
    public function getLenderDepositedAmount(\lenders_accounts $lender)
    {
        $queryBuilder = $this->bdd->createQueryBuilder();
        $queryBuilder
            ->select('SUM(montant) / 100')
            ->from('transactions')
            ->andWhere('etat = 1')
            ->andWhere('status = 1')
            ->andWhere('id_client = :id_client')
            ->andWhere('type_transaction IN (:types)')
            ->setParameter('id_client', $lender->id_client_owner)
            ->setParameter('types', [transactions_types::TYPE_LENDER_SUBSCRIPTION, transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT, transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        $statement = $queryBuilder->execute();
        return (float) $statement->fetchColumn();
    }
}
