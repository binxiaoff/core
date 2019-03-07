<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{Bids, Loans as LoansEntity, ProjectsStatus};

class loans extends loans_crud
{
    private $aAcceptedBids;

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
        $nbValid = $this->counter('id_project = ' . $id_project . ' AND id_wallet = ' . $id_lender . ' AND status = ' . LoansEntity::STATUS_ACCEPTED);

        $sql = 'SELECT SUM(amount) AS solde FROM loans WHERE id_project = ' . $id_project . ' AND id_wallet = ' . $id_lender . ' AND status = ' . LoansEntity::STATUS_ACCEPTED;

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
            SELECT COUNT(DISTINCT id_wallet) 
            FROM loans
            WHERE id_project = :projectId AND status = :status';
        $statement = $this->bdd->executeCacheQuery(
            $query,
            ['projectId' => $projectId, 'status' => LoansEntity::STATUS_ACCEPTED],
            ['projectId' => \PDO::PARAM_INT, 'status' => \PDO::PARAM_INT],
            new \Doctrine\DBAL\Cache\QueryCacheProfile(300, md5(__METHOD__))
        );
        $result = $statement->fetchColumn();
        $statement->closeCursor();

        return (int) $result;
    }

    public function getPreteursDetail($id_project, $dateDER)
    {
        $sql = '
            SELECT
                c.id_client,
                c.email,
                l.id_wallet,
                SUM(IF(DATE(e.date_echeance) <= "' . $dateDER . '", capital, 0)) AS capital_echus,
                SUM(IF(DATE(e.date_echeance) <= "' . $dateDER . '", interets, 0)) AS interets_echus,
                SUM(IF(DATE(e.date_echeance) > "' . $dateDER . '", capital, 0)) AS capital_restant_du,
                SUM(IF(DATE(e.date_echeance) > "' . $dateDER . '" AND e.date_echeance < DATE_ADD("' . $dateDER . '", INTERVAL 45 DAY), interets, 0)) AS interets_next
            FROM loans l
            LEFT JOIN echeanciers e ON e.id_lender = l.id_wallet AND e.id_project = l.id_project
            LEFT JOIN wallet w ON e.id_lender = w.id
            LEFT JOIN clients c ON w.id_client = c.id_client
            WHERE l.id_project = ' . $id_project . ' AND l.status = ' . LoansEntity::STATUS_ACCEPTED . '
            GROUP BY id_wallet';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function getProjectsCount($id_lender)
    {
        $sql = 'SELECT COUNT(DISTINCT id_project) FROM loans WHERE id_wallet = ' . $id_lender . ' AND status = ' . LoansEntity::STATUS_ACCEPTED;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result));
    }

    // retourne la moyenne des prets validés d'un projet
    public function getAvgLoans($id_project, $champ = 'amount')
    {
        $sql = 'SELECT AVG(' . $champ . ') as avg FROM loans WHERE id_project = ' . $id_project . ' AND status = ' . LoansEntity::STATUS_ACCEPTED;

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
        $sql = 'SELECT IFNULL(ROUND(SUM(rate * amount) / SUM(amount), 2), 0) AS avg FROM loans WHERE id_project = ' . $id_project . ' AND id_wallet = ' . $id_lender . ' AND status = ' . LoansEntity::STATUS_ACCEPTED;

        $result = $this->bdd->query($sql);
        return $this->bdd->result($result);
    }

    // retourne la moyenne des prets validés d'un preteur
    public function getAvgPrets($id_lender)
    {
        $result = $this->bdd->query('
            SELECT IFNULL(ROUND(SUM(rate * amount) / SUM(amount), 2), 0)
            FROM loans 
            WHERE id_wallet = ' . $id_lender . ' AND status = ' . LoansEntity::STATUS_ACCEPTED
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
            WHERE id_wallet = ' . $id_lender . ' AND status = ' . LoansEntity::STATUS_ACCEPTED
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

    /**
     * List all lender loans grouped by project
     *
     * @param int         $idLender
     * @param string|null $order
     * @param int|null    $year
     * @param bool        $includePendingBids
     *
     * @return array
     * @throws Exception
     */
    public function getSumLoansByProject(int $idLender, $order = null, $year = null, $includePendingBids = false): array
    {
        $query = '
            SELECT
                MAX(l.added) AS maxAdded,
                l.id_project,
                p.title,
                p.period,
                p.slug,
                p.risk,
                p.status AS project_status,
                ps.label AS project_status_label,
                IFNULL((SELECT MIN(added) FROM projects_status_history WHERE id_project = p.id_project AND id_project_status IN (SELECT id_project_status FROM projects_status WHERE status IN (:repaidStatus))), "") AS final_repayment_date,
                ROUND(SUM(l.amount) / 100, 2) AS amount,
                ROUND(SUM(l.rate * l.amount) / SUM(l.amount), 2) AS rate,
                COUNT(DISTINCT l.id_loan) AS nb_loan,
                l.id_loan AS id_loan_if_one_loan,
                YEAR(l.added) AS loan_year,
                l.id_type_contract,
                DATE(first_repayment.date_echeance) AS debut,
                DATE((SELECT MAX(date_echeance) FROM echeanciers WHERE id_loan = l.id_loan)) AS fin,
                DATE((SELECT MIN(date_echeance) FROM echeanciers WHERE id_loan = l.id_loan AND status = ' . LoansEntity::STATUS_ACCEPTED . ')) AS next_echeance,
                ROUND(SUM(first_repayment.montant) / 100, 2) AS monthly_repayment_amount,
                (SELECT ROUND(SUM(capital - capital_rembourse) / 100, 2) FROM echeanciers WHERE id_project = p.id_project AND id_lender = l.id_wallet) AS remaining_capital
            FROM loans l
            INNER JOIN projects p ON l.id_project = p.id_project
            INNER JOIN projects_status ps ON p.status = ps.status
            INNER JOIN echeanciers first_repayment ON (l.id_loan = first_repayment.id_loan AND first_repayment.ordre = 1)
            WHERE l.id_wallet = :idLender
                AND l.status = ' . LoansEntity::STATUS_ACCEPTED .
                (null === $year ? '' : ' AND YEAR(l.added) = :year') . '
            GROUP BY l.id_project';

        if ($includePendingBids) {
            $query .= '
            UNION
            
            SELECT
                MAX(b.added) AS maxAdded,
                b.id_project,
                p.title,
                p.period,
                p.slug,
                p.risk,
                p.status AS project_status,
                ps.label AS project_status_label,
                NULL AS final_repayment_date,
                ROUND(SUM(b.amount) / 100, 2) AS amount,
                ROUND(SUM(b.rate * b.amount) / SUM(b.amount), 2) AS rate,
                0 AS nb_loan,
                NULL AS id_loan_if_one_loan,
                YEAR(MAX(b.added)) AS loan_year,
                NULL AS id_type_contract,
                p.date_fin AS debut,
                NULL AS fin,
                NULL AS next_echeance,
                NULL AS monthly_repayment_amount,
                NULL AS remaining_capital
            FROM bids b
            INNER JOIN projects p ON b.id_project = p.id_project
            INNER JOIN projects_status ps ON p.status = ps.status
            WHERE b.id_wallet = :idLender
                AND p.status IN (:fundedStatus)
                AND b.status = ' . Bids::STATUS_ACCEPTED . '
            GROUP BY p.id_project';
        }

        $query .= ' ORDER BY ' . (null === $order ? 'maxAdded DESC' : $order);

        $statement = $this->bdd->executeQuery($query, [
                'repaidStatus'  => [ProjectsStatus::STATUS_REPAID],
                'fundedStatus'  => [ProjectsStatus::STATUS_ONLINE, ProjectsStatus::STATUS_FUNDED],
                'idLender'      => $idLender,
                'year'          => $year
            ], [
                'repaidStatus'  => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                'fundedStatus'  => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
                'idLender'      => PDO::PARAM_INT,
                'year'          => PDO::PARAM_INT
            ]
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC);
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
     * @param int $deferredDuration
     *
     * @return array
     */
    public function getDeferredRepaymentSchedule($deferredDuration)
    {
        $schedule     = [];
        $loanDuration = $this->getMonthNb();

        foreach ($this->getBids() as $bid) {
            $amount      = $bid['accepted_amount'] / 100;
            $rate        = $bid['rate'] / 100;
            $bidSchedule = \repayment::getDeferredRepaymentSchedule($amount, $rate, $loanDuration, $deferredDuration);

            //Group the schedule of all bid of a loan
            foreach ($bidSchedule as $month => $repayment) {
                if (isset($schedule[$month])) {
                    foreach ($repayment as $sKey => $fValue) {
                        $schedule[$month][$sKey] += $fValue;
                    }
                } else {
                    $schedule[$month] = $repayment;
                }
            }
        }

        return $schedule;
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
        $aLoans            = $this->select('id_project = ' . $iProjectId . ' AND id_wallet = ' . $iLenderId);
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
                    FROM (SELECT sum(amount) / count(DISTINCT id_wallet) AS amount
                          FROM loans
                          GROUP BY id_project) AS avgProject';
        $statement = $this->bdd->executeQuery($query);

        return $statement->fetchColumn(0);
    }

    public function getFirstLoanYear($lenderId)
    {
        $sql = 'SELECT MIN(YEAR(added)) AS first_loan_year FROM loans WHERE id_wallet = :walletId';
        return $this->bdd->executeQuery($sql, ['walletId' => $lenderId], ['walletId' => \PDO::PARAM_INT] )->fetchColumn(0);
    }

    /**
     * @param bool $groupFirstYears
     *
     * @return mixed
     */
    public function sumLoansByCohort($groupFirstYears = true)
    {
        if ($groupFirstYears) {
            $cohortSelect = 'CASE LEFT(projects_status_history.added, 4)
                                WHEN 2013 THEN "2013-2014"
                                WHEN 2014 THEN "2013-2014"
                                ELSE LEFT(projects_status_history.added, 4)
                            END';
        } else {
            $cohortSelect = 'LEFT(projects_status_history.added, 4)';
        }

        $query = 'SELECT SUM(loans.amount)/100 AS amount,
                    (
                        SELECT ' . $cohortSelect . ' AS date_range
                        FROM projects_status_history
                        INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE  projects_status.status = ' . ProjectsStatus::STATUS_REPAYMENT . '
                          AND loans.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                    FROM loans
                      INNER JOIN projects on loans.id_project = projects.id_project AND projects.status >= ' . ProjectsStatus::STATUS_REPAYMENT . '
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
                          AND loans.id_wallet = :idLender';

        $statement = $this->bdd->executeQuery($query, ['projectStatus' => $projectStatus, 'idLender' => $idLender], ['projectStatus' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
