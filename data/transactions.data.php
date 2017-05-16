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
    const PAYMENT_TYPE_AUTO       = 1;
    const PAYMENT_TYPE_AMEX       = 2;
    const PAYMENT_TYPE_MASTERCARD = 3;

    const STATUS_PENDING  = 0;
    const STATUS_VALID    = 1;
    const STATUS_CANCELED = 3;

    public function __construct($bdd, $params = '')
    {
        parent::transactions($bdd, $params);
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
            WHERE status = ' . self::STATUS_VALID . '
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
            WHERE status = ' . self::STATUS_VALID . '
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
                AND t.status = ' . self::STATUS_VALID . '
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
                AND t.status = ' . self::STATUS_VALID . '
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
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
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
                                                    ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
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
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
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
                                                    ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
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
            ->andWhere('status = ' . self::STATUS_VALID)
            ->andWhere('id_client = :id_client')
            ->andWhere('type_transaction IN (:types)')
            ->setParameter('id_client', $lender->id_client_owner)
            ->setParameter('types', [transactions_types::TYPE_LENDER_SUBSCRIPTION, transactions_types::TYPE_LENDER_CREDIT_CARD_CREDIT, transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        $statement = $queryBuilder->execute();
        return (float) $statement->fetchColumn();
    }

    /**
     * @param DateTime $date
     * @return mixed
     */
    public function getLenderWithTransactionsSinceDate(\DateTime $date)
    {
        $queryBuilder = $this->bdd->createQueryBuilder();
        $queryBuilder
            ->select('DISTINCT id_client')
            ->from('transactions', 't')
            ->innerJoin('t', 'lenders_accounts', 'la', 't.id_client = la.id_client_owner')
            ->andWhere('t.status = ' . self::STATUS_VALID)
            ->andWhere('date_transaction >= :date')
            ->setParameter('date', $date->format('Y-m-d h:i:s'));

        $data = $queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }
}
