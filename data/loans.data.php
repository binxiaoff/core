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

class loans extends loans_crud
{
    const STATUS_ACCEPTED = 0;
    const STATUS_REJECTED = 1;

    private $aAcceptedBids;

    public function __construct($bdd, $params = '')
    {
        parent::loans($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql      = 'SELECT * FROM `loans`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));
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

        $sql = 'SELECT count(*) FROM `loans` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) $this->bdd->result($result);
    }

    public function exist($id, $field = 'id_loan')
    {
        $sql    = 'SELECT * FROM `loans` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function getBidsValid($id_project, $id_lender)
    {
        $nbValid = $this->counter('id_project = ' . $id_project . ' AND id_lender = ' . $id_lender . ' AND status = ' . self::STATUS_ACCEPTED);

        $sql = 'SELECT SUM(amount) AS solde FROM loans WHERE id_project = ' . $id_project . ' AND id_lender = ' . $id_lender . ' AND status = ' . self::STATUS_ACCEPTED;

        $result = $this->bdd->query($sql);
        $solde  = $this->bdd->result($result);
        if ($solde == '') {
            $solde = 0;
        } else {
            $solde = ($solde / 100);
        }

        return array('solde' => $solde, 'nbValid' => $nbValid);
    }

    public function getNbPreteurs($projectId)
    {
        $query = '
            SELECT COUNT(DISTINCT id_lender) 
            FROM loans
            WHERE id_project = :projectId AND status = :status';
        $statement = $this->bdd->executeCacheQuery(
            $query,
            ['projectId' => $projectId, 'status' => self::STATUS_ACCEPTED],
            ['projectId' => \PDO::PARAM_INT, 'status' => \PDO::PARAM_INT],
            new \Doctrine\DBAL\Cache\QueryCacheProfile(300, md5(__METHOD__))
        );
        $result = $statement->fetchColumn();
        $statement->closeCursor();

        return (int) $result;
    }

    public function getProjectLoansByLender($id_project)
    {
        $sql = '
            SELECT id_lender,
                SUM(amount) AS amount,
                COUNT(DISTINCT id_loan) AS cnt,
                GROUP_CONCAT(id_loan) AS loans
            FROM `loans`
            WHERE id_project = ' . $id_project . '
                AND status = ' . self::STATUS_ACCEPTED . '
            GROUP BY id_lender';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function getPreteursDetail($id_project, $dateDER)
    {
        $sql = '
            SELECT
                c.id_client,
                c.email,
                l.id_lender,
                SUM(IF(DATE(e.date_echeance) <= "' . $dateDER . '", capital, 0)) AS capital_echus,
                SUM(IF(DATE(e.date_echeance) <= "' . $dateDER . '", interets, 0)) AS interets_echus,
                SUM(IF(DATE(e.date_echeance) > "' . $dateDER . '", capital, 0)) AS capital_restant_du,
                SUM(IF(DATE(e.date_echeance) > "' . $dateDER . '" AND e.date_echeance < DATE_ADD("' . $dateDER . '", INTERVAL 45 DAY), interets, 0)) AS interets_next
            FROM loans l
            LEFT JOIN echeanciers e ON e.id_lender = l.id_lender AND e.id_project = l.id_project
            LEFT JOIN wallet w ON e.id_lender = w.id
            LEFT JOIN clients c ON w.id_client = c.id_client
            WHERE l.id_project = ' . $id_project . ' AND l.status = ' . self::STATUS_ACCEPTED . '
            GROUP BY id_lender';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function getProjectsCount($id_lender)
    {
        $sql = 'SELECT COUNT(DISTINCT id_project) FROM loans WHERE id_lender = ' . $id_lender . ' AND status = ' . self::STATUS_ACCEPTED;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result));
    }

    public function getLoansCount($id_lender)
    {
        $sql = 'SELECT COUNT(DISTINCT id_loan) FROM loans WHERE id_lender = ' . $id_lender . ' AND status = ' . self::STATUS_ACCEPTED;
        $result = $this->bdd->query($sql);
        return (int) $this->bdd->result($result);
    }

    // retourne la moyenne des prets validés d'un projet
    public function getAvgLoans($id_project, $champ = 'amount')
    {
        $sql = 'SELECT AVG(' . $champ . ') as avg FROM loans WHERE id_project = ' . $id_project . ' AND status = ' . self::STATUS_ACCEPTED;

        $result = $this->bdd->query($sql);
        $avg    = $this->bdd->result($result);
        if ($avg == '') {
            $avg = 0;
        }

        return $avg;
    }

    // retourne la moyenne des prets validés d'un preteur sur un projet
    public function getAvgLoansPreteur($id_project, $id_lender)
    {
        $sql = 'SELECT IFNULL(ROUND(SUM(rate * amount) / SUM(amount), 2), 0) AS avg FROM loans WHERE id_project = ' . $id_project . ' AND id_lender = ' . $id_lender . ' AND status = ' . self::STATUS_ACCEPTED;

        $result = $this->bdd->query($sql);
        return $this->bdd->result($result);
    }

    // retourne la moyenne des prets validés d'un preteur
    public function getAvgPrets($id_lender)
    {
        $result = $this->bdd->query('
            SELECT IFNULL(ROUND(SUM(rate * amount) / SUM(amount), 2), 0)
            FROM loans 
            WHERE id_lender = ' . $id_lender . ' AND status = ' . self::STATUS_ACCEPTED
        );
        return (float) $this->bdd->result($result);
    }

    // sum prêtée d'un lender
    public function sumPrets($id_lender)
    {
        $result  = $this->bdd->query('
            SELECT 
            IFNULL(ROUND(SUM(amount) / 100, 2), 0) 
            FROM loans
            WHERE id_lender = ' . $id_lender . ' AND status = ' . self::STATUS_ACCEPTED
        );
        return (float) $this->bdd->result($result);
    }

    // sum prêtée d'un du projet
    public function sumPretsProjet($id_project)
    {
        $sql = '
            SELECT IFNULL(ROUND(SUM(amount) / 100, 2), 0)
            FROM loans 
            WHERE id_project = ' . $id_project;

        $result = $this->bdd->query($sql);
        return (float) $this->bdd->result($result);
    }

    public function sum($where = '', $champ)
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT SUM(' . $champ . ') FROM `loans` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) $this->bdd->result($result);
    }

    // On recup la liste des loans d'un preteur en les regoupant par projet
    public function getSumLoansByProject($lenderAccountId, $order = null, $year = null, $projectStatus = null)
    {
        if (is_array($projectStatus)) {
            $projectStatus = implode(', ', $projectStatus);
        }

        $repaidStatus = [
            \echeanciers::STATUS_REPAID,
            \echeanciers::STATUS_PARTIALLY_REPAID
        ];
        $result       = [];
        $query        = '
            SELECT
                l.id_project,
                p.title,
                p.slug,
                c.city,
                c.zip,
                p.risk,
                p.status AS project_status,
                ps.label AS project_status_label,
                IFNULL((SELECT added FROM projects_status_history WHERE id_project = p.id_project AND id_project_status IN (SELECT id_project_status FROM projects_status WHERE status IN (' . implode(', ', [\projects_status::REMBOURSE, \projects_status::REMBOURSEMENT_ANTICIPE]) . '))), "") AS final_repayment_date,
                SUM(ROUND(l.amount / 100, 2)) AS amount,
                ROUND(SUM(rate * l.amount) / SUM(l.amount), 2) AS rate,
                COUNT(l.id_loan) AS nb_loan,
                l.id_loan AS id_loan_if_one_loan,
                YEAR(l.added) AS loan_year,
                l.id_type_contract,
                DATE((SELECT MIN(e.date_echeance) FROM echeanciers e WHERE e.id_loan = l.id_loan AND e.ordre = 1)) AS debut,
                DATE((SELECT MAX(e1.date_echeance) FROM echeanciers e1 WHERE e1.id_loan = l.id_loan)) AS fin,
                DATE((SELECT MIN(e2.date_echeance) FROM echeanciers e2 WHERE e2.id_loan = l.id_loan AND e2.status = 0)) AS next_echeance,
                SUM((SELECT (ROUND(e3.montant / 100, 2) -
                ROUND(
                   (SELECT SUM(IFNULL(tax.amount, 0) / 100)
                   FROM tax
                   WHERE tax.id_transaction =
                     (SELECT t.id_transaction
                      FROM transactions t
                      WHERE t.id_echeancier = e3.id_echeancier AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '))
                , 2)
                ) FROM echeanciers e3 WHERE e3.id_loan = l.id_loan AND e3.status IN (' . implode(', ', $repaidStatus) . ') AND e3.date_echeance = (SELECT MIN(e4.date_echeance) FROM echeanciers e4 WHERE e4.id_loan = l.id_loan AND e4.status IN (' . implode(', ', $repaidStatus) . ') ) LIMIT 1)) AS last_perceived_repayment,
                (SELECT SUM(ROUND(e.montant / 100, 2)) FROM echeanciers e WHERE e.id_project = l.id_project AND e.ordre = 1 AND e.id_lender = l.id_lender) AS monthly_repayment_amount,
                (SELECT ROUND(SUM(e5.capital - e5.capital_rembourse) / 100, 2) FROM echeanciers e5 WHERE e5.id_loan = l.id_loan) AS remaining_capital
            FROM loans l
            INNER JOIN projects p ON l.id_project = p.id_project
            INNER JOIN companies c ON p.id_company = c.id_company
            INNER JOIN projects_status ps ON p.status = ps.status
            WHERE l.id_lender = ' . $lenderAccountId . '
                AND l.status = ' . self::STATUS_ACCEPTED . '
                ' . (null === $year ? '' : 'AND YEAR(l.added) = "' . $year . '"') . '
                ' . (null === $projectStatus ? '' : 'AND p.status IN (' . $projectStatus . ')') . '
            GROUP BY l.id_project
            ORDER BY ' . (null === $order ? 'l.added DESC' : $order);

        $statement = $this->bdd->query($query);
        while ($record = $this->bdd->fetch_assoc($statement)) {
            $result[] = $record;
        }

        return $result;
    }

    public function getBids($iLoanId = null)
    {
        if (null == $iLoanId) {
            $iLoanId = $this->id_loan;
        }

        if ($iLoanId) {
            $sQuery = ' SELECT b.*, ab.amount as accepted_amount
                        FROM accepted_bids ab
                        INNER JOIN bids b ON ab.id_bid = b.id_bid
                        WHERE ab.id_loan = ' . $iLoanId;
            $rQuery = $this->bdd->query($sQuery);
            $aBids  = array();
            while ($aRow = $this->bdd->fetch_array($rQuery)) {
                $aBids[] = $aRow;
            }
            return $aBids;
        }
    }

    public function getRepaymentSchedule($iLoanId = null)
    {
        if (null !== $iLoanId) {
            $this->get($iLoanId);
        }

        $iMonthNb         = $this->getMonthNb();
        $aBids            = $this->getBids();
        $aScheduleGrouped = array();
        foreach ($aBids as $aBid) {
            $aSchedule = \repayment::getRepaymentSchedule($aBid['accepted_amount'] / 100, $iMonthNb, $aBid['rate'] / 100);
            //Group the schedule of all bid of a loan
            foreach ($aSchedule as $iOrder => $aRepayment) {
                if (isset($aScheduleGrouped[$iOrder])) {
                    foreach ($aRepayment as $sKey => $fValue) {
                        $aScheduleGrouped[$iOrder][$sKey] += $fValue;
                    }
                } else {
                    $aScheduleGrouped[$iOrder] = $aRepayment;
                }
            }
        }
        return $aScheduleGrouped;
    }

    /**
     * @param int $projectId
     * @return bool|int
     */
    public function getMonthNb($projectId = null)
    {
        if (null === $projectId) {
            $projectId = $this->id_project;
        }

        if ($projectId) {
            $sQuery = 'SELECT period FROM projects WHERE id_project = :projectId Limit 1';

            try {
                $statement = $this->bdd->executeCacheQuery($sQuery, array('projectId' => $projectId), array('projectId' => \PDO::PARAM_INT), new \Doctrine\DBAL\Cache\QueryCacheProfile(300, md5(__METHOD__)));
                $result = $statement->fetchAll(PDO::FETCH_COLUMN);
                $statement->closeCursor();

                if (empty($result)) {
                    return false;
                }
            } catch (\Doctrine\DBAL\DBALException $ex) {
                return false;
            }
            return (int)array_shift($result);
        }
        return false;
    }

    public function getWeightedAverageInterestRateForLender($iLenderId, $iProjectId)
    {
        $aLoans            = $this->select('id_project = ' . $iProjectId . ' AND id_lender = ' . $iLenderId);
        $iSumOfAmountXRate = 0;
        $iSumAmount        = 0;

        foreach ($aLoans as $aLoan) {
            $iSumOfAmountXRate += $aLoan['amount'] * $aLoan['rate'];
            $iSumAmount += $aLoan['amount'];
        }

        return $iSumOfAmountXRate / $iSumAmount;
    }

    public function addAcceptedBid($iBidId, $fAmount)
    {
        $this->aAcceptedBids[] = array('bid_id' => $iBidId, 'amount' => $fAmount);
    }

    public function getAcceptedBids()
    {
        return $this->aAcceptedBids;
    }

    public function unsetData()
    {
        parent::unsetData();
        $this->aAcceptedBids = array();
    }

    public function getAverageLoanAmount()
    {
        $query = 'SELECT AVG(avgProject.amount) / 100
                    FROM (SELECT sum(amount) / count(DISTINCT id_lender) AS amount
                          FROM loans
                          GROUP BY id_project) AS avgProject';
        $statement = $this->bdd->executeQuery($query);

        return $statement->fetchColumn(0);
    }

    public function getFirstLoanYear($lenderId)
    {
        $sql = 'SELECT MIN(YEAR(added)) AS first_loan_year FROM loans WHERE id_lender = :lenderId';
        return $this->bdd->executeQuery($sql, ['lenderId' => $lenderId], ['lenderId' => \PDO::PARAM_INT] )->fetchColumn(0);
    }

    public function sumLoansByCohort()
    {
        $query = 'SELECT SUM(loans.amount)/100 AS amount,
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
                          AND loans.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                    FROM loans
                      INNER JOIN projects on loans.id_project = projects.id_project AND projects.status >= '. \projects_status::REMBOURSEMENT .'
                    GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int $idLender
     * @param array $projectStatus
     * @return mixed
     */
    public function getLoansForProjectsWithStatus($idLender, array $projectStatus)
    {
        $query = 'SELECT *
                    FROM loans
                      INNER JOIN projects ON loans.id_project = projects.id_project
                    WHERE projects.status IN (:projectStatus)
                          AND loans.id_lender = :idLender';

        $statement = $this->bdd->executeQuery($query, ['projectStatus' => $projectStatus, 'idLender' => $idLender], ['projectStatus' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
