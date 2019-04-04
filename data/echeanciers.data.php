<?php

use Unilend\Entity\{CompanyStatus, Echeanciers as EcheanciersEntity, Loans, ProjectsStatus, UnilendStats};
use Unilend\Service\StatisticsManager;

class echeanciers extends echeanciers_crud
{
    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql      = 'SELECT * FROM echeanciers' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));
        $resultat = $this->bdd->query($sql);
        $result   = [];
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

        $result = $this->bdd->query('SELECT COUNT(*) FROM echeanciers' . $where);
        return (int) $this->bdd->result($result);
    }

    public function exist($id, $field = 'id_echeancier')
    {
        $result = $this->bdd->query('SELECT * FROM echeanciers WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }

    /**
     * @param array $selector
     *
     * @return string
     */
    public function getTotalAmount(array $selector)
    {
        return $this->getPartialSum('capital + interets', $selector);
    }

    /**
     * @param array $selector
     *
     * @return string
     */
    public function getTotalInterests(array $selector)
    {
        return $this->getPartialSum('interets', $selector);
    }

    /**
     * @param array $selector
     *
     * @return string
     */
    public function getOwedCapital(array $selector)
    {
        return $this->getPartialSum('capital - capital_rembourse', $selector, [EcheanciersEntity::STATUS_PENDING, EcheanciersEntity::STATUS_PARTIALLY_REPAID]);
    }

    /**
     * @param array $selector
     *
     * @return string
     */
    public function getOwedInterests(array $selector)
    {
        return $this->getPartialSum('interets - interets_rembourses', $selector, [EcheanciersEntity::STATUS_PENDING, EcheanciersEntity::STATUS_PARTIALLY_REPAID]);
    }

    /**
     * @param int       $projectId
     * @param \DateTime $endDate
     *
     * @return string
     */
    public function getUnpaidAmountAtDate($projectId, \DateTime $endDate)
    {
        $bind     = [
            'id_project'       => $projectId,
            'loan_status'      => Loans::STATUS_ACCEPTED,
            'repayment_status' => [EcheanciersEntity::STATUS_PENDING, EcheanciersEntity::STATUS_PARTIALLY_REPAID],
            'date_echeance'    => $endDate->format('Y-m-d 23:59:59')
        ];
        $bindType = [
            'id_project'       => \PDO::PARAM_INT,
            'loan_status'      => \PDO::PARAM_INT,
            'repayment_status' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'date_echeance'    => \PDO::PARAM_STR
        ];
        $query    = '
            SELECT SUM(e.capital - e.capital_rembourse + e.interets - e.interets_rembourses)
            FROM echeanciers e
            INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE l.status = :loan_status
              AND e.id_project = :id_project
              AND e.status IN (:repayment_status)
              AND e.date_echeance <= :date_echeance';
        return bcdiv($this->bdd->executeQuery($query, $bind, $bindType)
            ->fetchColumn(0), 100, 2);
    }

    /**
     * @param int $projectId
     * @param int $due
     *
     * @return string
     * @throws Exception
     */
    public function getRemainingCapitalAtDue($projectId, $due)
    {
        $bind     = [
            'id_project'       => $projectId,
            'loan_status'      => Loans::STATUS_ACCEPTED,
            'repayment_status' => [EcheanciersEntity::STATUS_PENDING, EcheanciersEntity::STATUS_PARTIALLY_REPAID],
            'ordre'            => $due
        ];
        $bindType = [
            'id_project'       => \PDO::PARAM_INT,
            'loan_status'      => \PDO::PARAM_INT,
            'repayment_status' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'ordre'            => \PDO::PARAM_INT
        ];
        $query    = '
            SELECT SUM(e.capital - e.capital_rembourse)
            FROM echeanciers e
            INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE l.status = :loan_status
              AND e.id_project = :id_project
              AND e.status IN (:repayment_status)
              AND e.ordre >= :ordre';
        return (float) bcdiv($this->bdd->executeQuery($query, $bind, $bindType)
            ->fetchColumn(0), 100, 2);
    }

    /**
     * @param array $selector
     *
     * @return string
     */
    public function getRepaidAmount(array $selector)
    {
        return bcadd($this->getRepaidCapital($selector), $this->getRepaidInterests($selector), 2);
    }

    /**
     * @param array $selector
     *
     * @return string
     */
    public function getRepaidCapital(array $selector)
    {
        return $this->getPartialSum('capital_rembourse', $selector, [EcheanciersEntity::STATUS_REPAID, EcheanciersEntity::STATUS_PARTIALLY_REPAID]);
    }

    /**
     * @param array $selector
     *
     * @return string
     */
    public function getRepaidInterests(array $selector)
    {
        return $this->getPartialSum('interets_rembourses', $selector, [EcheanciersEntity::STATUS_REPAID, EcheanciersEntity::STATUS_PARTIALLY_REPAID], EcheanciersEntity::IS_NOT_EARLY_REPAID);
    }

    /**
     * @param string   $amountType
     * @param array    $selector
     * @param array    $status
     * @param int|null $earlyRepaymentStatus
     *
     * @return string
     */
    private function getPartialSum($amountType, array $selector, array $status = [], $earlyRepaymentStatus = null)
    {
        $query = '
            SELECT SUM(' . $amountType . ')
            FROM echeanciers e
            INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE l.status = ' . Loans::STATUS_ACCEPTED;

        if (false === empty($selector)) {
            $query .= ' AND e.' . $this->implodeSelector($selector);
        }

        if (false === empty($status)) {
            $query .= ' AND e.status IN (' . implode(', ', $status) . ')';
        }

        if (null !== $earlyRepaymentStatus) {
            $query .= ' AND e.status_ra = ' . $earlyRepaymentStatus;
        }

        $result = $this->bdd->query($query);
        return bcdiv($this->bdd->result($result), 100, 2);
    }

    /**
     * @param array $selector
     *
     * @return array
     */
    public function getYearlySchedule(array $selector)
    {
        $result      = [];
        $queryResult = $this->bdd->query('
            SELECT YEAR(date_echeance) AS annee,
                SUM(capital) AS capital,
                SUM(interets) AS interets
            FROM echeanciers
            WHERE ' . $this->implodeSelector($selector) . '
            GROUP BY annee'
        );

        while ($record = $this->bdd->fetch_assoc($queryResult)) {
            $result[$record['annee']] = $record;
        }
        return $result;
    }

    /**
     * @param array $selector
     *
     * @return string
     */
    private function implodeSelector(array $selector)
    {
        return implode(' AND e.', array_map(
            function ($key, $value) {
                return $key . ' = ' . $value;
            },
            array_keys($selector),
            $selector
        ));
    }

    /**
     * @param int    $lenderId
     * @param string $startDate
     * @param string $endDate
     *
     * @return int|string
     * @throws Exception
     */
    public function getNextRepaymentAmountInDateRange($lenderId, $startDate, $endDate)
    {
        return $this->getRepaymentAmountInDateRange($lenderId, $startDate, $endDate, 'e.capital + e.interets', [EcheanciersEntity::STATUS_PENDING]);
    }

    /**
     * @param int      $projectId
     * @param DateTime $startDate
     *
     * @return string
     * @throws Exception
     */
    public function getTotalComingCapitalByProject($projectId, DateTime $startDate = null)
    {
        if ($startDate === null) {
            $startDate = new DateTime();
        }
        $bind     = [
            'id_project'       => $projectId,
            'loan_status'      => Loans::STATUS_ACCEPTED,
            'repayment_status' => EcheanciersEntity::STATUS_PENDING,
            'date_echeance'    => $startDate->format('Y-m-d')
        ];
        $bindType = [
            'id_project'       => \PDO::PARAM_INT,
            'loan_status'      => \PDO::PARAM_INT,
            'repayment_status' => \PDO::PARAM_INT,
            'date_echeance'    => \PDO::PARAM_STR
        ];
        $query    = '
            SELECT SUM(e.capital)
            FROM echeanciers e
            INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE l.status = :loan_status
              AND e.id_project = :id_project
              AND e.status = :repayment_status
              AND date(e.date_echeance) > :date_echeance';
        return bcdiv($this->bdd->executeQuery($query, $bind, $bindType)
            ->fetchColumn(0), 100, 2);
    }

    /**
     * @param int      $lenderId
     * @param int      $startDate
     * @param string   $endDate
     * @param string   $amountType
     * @param array    $repaymentStatus
     *
     * @return int|string
     * @throws Exception
     */
    private function getRepaymentAmountInDateRange($lenderId, $startDate, $endDate, $amountType, $repaymentStatus)
    {
        $bind = [
            'loan_status'      => Loans::STATUS_ACCEPTED,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'repayment_status' => $repaymentStatus
        ];

        $bindType = [
            'loan_status'      => \PDO::PARAM_INT,
            'start_date'       => \PDO::PARAM_STR,
            'end_date'         => \PDO::PARAM_STR,
            'repayment_status' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
        ];

        if (in_array(EcheanciersEntity::STATUS_PENDING, $repaymentStatus)) {
            $date = 'date_echeance';
        } else {
            $date = 'date_echeance_reel';
        }

        $query = '
            SELECT SUM(' . $amountType . ')
            FROM echeanciers e
            INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE l.status = :loan_status
              AND e.' . $date . ' BETWEEN :start_date AND :end_date
              AND e.status IN (:repayment_status) ';

        if (false === is_null($lenderId)) {
            $bind['id_lender']     = $lenderId;
            $bindType['id_lender'] = \PDO::PARAM_INT;
            $query                 .= ' AND e.id_lender = :id_lender ';
        }

        $statement = $this->bdd->executeQuery($query, $bind, $bindType, new \Doctrine\DBAL\Cache\QueryCacheProfile(\Unilend\librairies\CacheKeys::MEDIUM_TIME, md5(__METHOD__)));
        $result    = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        if (isset($result[0]) && isset(array_values($result[0])[0])) {
            return bcdiv(array_values($result[0])[0], 100, 2);
        }

        return 0;
    }

    /**
     * @param int $projectId
     *
     * @return array
     */
    public function getMonthlyScheduleByProject($projectId)
    {
        $sql = '
            SELECT ordre,
                SUM(montant) AS montant,
                SUM(capital) AS capital,
                SUM(interets) AS interets,
                date_echeance_emprunteur,
                status_emprunteur
            FROM echeanciers
            WHERE id_project = :id_project 
            GROUP BY ordre';

        $res       = [];
        $statement = $this->bdd->executeQuery($sql,
            ['id_project' => $projectId],
            ['id_project' => \PDO::PARAM_INT],
            new \Doctrine\DBAL\Cache\QueryCacheProfile(\Unilend\librairies\CacheKeys::SHORT_TIME, md5(__METHOD__))
        );

        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        foreach ($result as $record) {
            $res[$record['ordre']] = [
                'montant'                  => bcdiv($record['montant'], 100, 2),
                'capital'                  => bcdiv($record['capital'], 100, 2),
                'interets'                 => bcdiv($record['interets'], 100, 2),
                'date_echeance_emprunteur' => $record['date_echeance_emprunteur'],
                'status_emprunteur'        => $record['status_emprunteur']
            ];
        }

        return $res;
    }

    /**
     * @param int $lenderId
     *
     * @return array
     */
    public function getProblematicProjects($lenderId)
    {
        $sql = '
            SELECT
              IFNULL(ROUND(SUM(e.capital - e.capital_rembourse) / 100, 2), 0) AS capital,
              IFNULL(ROUND(SUM(e.interets - e.interets_rembourses) / 100, 2), 0) AS interests,
              COUNT(DISTINCT(e.id_project)) AS projects
            FROM echeanciers e
            LEFT JOIN echeanciers unpaid ON unpaid.id_echeancier = e.id_echeancier AND unpaid.status = ' . EcheanciersEntity::STATUS_PENDING . ' 
              AND DATEDIFF(NOW(), unpaid.date_echeance) > ' . UnilendStats::DAYS_AFTER_LAST_PROBLEM_STATUS_FOR_STATISTIC_LOSS . '
            INNER JOIN loans l ON l.id_wallet = e.id_lender AND l.id_loan = e.id_loan
            INNER JOIN projects p ON p.id_project = e.id_project
            INNER JOIN companies c ON c.id_company = p.id_company
            INNER JOIN company_status cs ON cs.id = c.id_status
            WHERE e.status IN(' . EcheanciersEntity::STATUS_PENDING . ', ' . EcheanciersEntity::STATUS_PARTIALLY_REPAID . ')
                AND l.status = ' . Loans::STATUS_ACCEPTED . '
                AND (cs.label != "' . CompanyStatus::STATUS_IN_BONIS . '" OR unpaid.date_echeance IS NOT NULL)
                AND e.id_lender = :id_lender';

        return $this->bdd->executeQuery($sql, ['id_lender' => $lenderId])->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param int    $projectId
     * @param int    $ordre
     * @param string $annuler
     */
    public function updateStatusEmprunteur($projectId, $ordre, $annuler = '')
    {
        if ($annuler != '') {
            $sql = '
                UPDATE echeanciers 
                SET status_emprunteur = ' . EcheanciersEntity::STATUS_PENDING . ', 
                    date_echeance_emprunteur_reel = "0000-00-00 00:00:00", 
                    updated = "' . date('Y-m-d H:i:s') . '" 
                WHERE id_project = ' . $projectId . ' AND ordre = ' . $ordre;
        } else {
            $sql = '
                UPDATE echeanciers 
                SET status_emprunteur = ' . EcheanciersEntity::IS_EARLY_REPAID . ',
                    date_echeance_emprunteur_reel = "' . date('Y-m-d H:i:s') . '",
                    updated = "' . date('Y-m-d H:i:s') . '"
                WHERE id_project = ' . $projectId . ' AND ordre = ' . $ordre;
        }

        $this->bdd->query($sql);
    }

    // on recup la premiere echeance d'un pret d'un preteur
    public function getPremiereEcheancePreteurByLoans($projectId, $id_lender, $id_loan)
    {
        // premiere echeance
        $PremiereEcheance = $this->select('ordre = 1 AND id_project = ' . $projectId . ' AND id_lender = ' . $id_lender . ' AND id_loan = ' . $id_loan, '', 0, 1);
        return $PremiereEcheance[0];
    }

    // premiere echance emprunteur
    public function getDatePremiereEcheance($projectId)
    {
        // premiere echeance
        $PremiereEcheance = $this->select('ordre = 1 AND id_project = ' . $projectId, '', 0, 1);
        return $PremiereEcheance[0]['date_echeance_emprunteur'];
    }

    public function getDateDerniereEcheancePreteur($projectId)
    {
        $result = $this->bdd->query('SELECT MAX(date_echeance) FROM echeanciers WHERE id_project = ' . $projectId);
        return $this->bdd->result($result);
    }

    public function onMetAjourLesDatesEcheances($projectId, $ordre, $date_echeance, $date_echeance_emprunteur)
    {
        $sql = '
            UPDATE echeanciers 
            SET date_echeance = "' . $date_echeance . '", 
                date_echeance_emprunteur = "' . $date_echeance_emprunteur . '", 
                updated = "' . date('Y-m-d H:i:s') . '" 
            WHERE id_project = ' . $projectId . ' 
                AND status_emprunteur = ' . EcheanciersEntity::STATUS_PENDING . ' 
                AND ordre = "' . $ordre . '" ';
        $this->bdd->query($sql);
    }

    public function getRepaymentOfTheDay(\DateTime $date)
    {
        $bind = ['formatedDate' => $date->format('Y-m-d')];
        $type = ['formatedDate' => \PDO::PARAM_STR];

        $sql = '
            SELECT id_project,
              ordre,
              COUNT(*) AS nb_repayment,
              COUNT(CASE status WHEN ' . EcheanciersEntity::STATUS_REPAID . ' THEN 1 ELSE NULL END) AS nb_repayment_paid
            FROM echeanciers
            WHERE DATE(date_echeance) = :formatedDate AND status_ra = ' . EcheanciersEntity::IS_NOT_EARLY_REPAID. '
            GROUP BY id_project, ordre';

        $statement = $this->bdd->executeQuery($sql, $bind, $type);
        $result    = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $result;
    }

    // retourne la somme total a rembourser pour un projet
    public function get_liste_preteur_on_project($projectId = '')
    {
        $sql = 'SELECT * FROM `echeanciers`
                      WHERE id_project = ' . $projectId . '
                      GROUP BY id_loan';

        $resultat = $this->bdd->query($sql);
        $result   = [];
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function getLastOrder($iProjectID, $sDate = 'NOW()', $sInterval = 3)
    {
        $resultat = $this->bdd->query('
            SELECT *
            FROM echeanciers
            WHERE id_project = ' . $iProjectID . '
                AND DATE_ADD(date_echeance, INTERVAL ' . $sInterval . ' DAY) > ' . $sDate . '
                AND id_lender = (SELECT id_lender FROM echeanciers WHERE id_project = ' . $iProjectID . ' LIMIT 1)
            GROUP BY id_project
            ORDER BY ordre ASC
            LIMIT 1'
        );

        return $this->bdd->fetch_assoc($resultat);
    }


    /**
     * @param int      $lenderId
     * @param int|null $projectId
     *
     * @return mixed
     */
    public function getFirstAndLastRepaymentDates($lenderId, $projectId = null)
    {
        $params['id_lender'] = $lenderId;
        $binds['id_lender']  = \PDO::PARAM_INT;
        $sql                 = '
            SELECT
              DATE(MIN(e.date_echeance)) AS first_repayment_date,
              Date(MAX(e.date_echeance)) AS last_repayment_date
            FROM echeanciers e
            WHERE e.id_lender = :id_lender';

        if (false === empty($iProjectId)) {
            $sql                  .= ' AND e.id_project = :id_project';
            $params['id_project'] = $projectId;
            $binds['id_project']  = \PDO::PARAM_INT;
        }

        /** @var \Doctrine\DBAL\Statement $statement */
        $statement = $this->bdd->executeQuery($sql, $params, $binds);

        return $statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param bool $groupFirstYears
     *
     * @return mixed
     */
    public function getTotalRepaidInterestByCohort($groupFirstYears = true)
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

        $query = 'SELECT
                      SUM(interets_rembourses)/100 AS amount,
                      (
                        SELECT ' . $cohortSelect . ' AS date_range
                        FROM projects_status_history
                        INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE  projects_status.status = ' . \projects_status::REMBOURSEMENT . '
                          AND echeanciers.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                    FROM echeanciers
                        WHERE echeanciers.status IN (' . EcheanciersEntity::STATUS_REPAID . ', ' . EcheanciersEntity::STATUS_PARTIALLY_REPAID . ')
                    GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $contractType
     * @param int|null    $delay
     *
     * @return array
     * @throws Exception
     */
    public function getProblematicOwedCapitalByProjects(string $contractType, ?int $delay = null): array
    {
        $delayQuery = '';
        $params     = [
            'problem'      => ProjectsStatus::STATUS_LOSS,
            'repayment'    => ProjectsStatus::STATUS_REPAYMENT,
            'contractType' => $contractType,
            'repaid'       => EcheanciersEntity::STATUS_REPAID,
            'accepted'     => Loans::STATUS_ACCEPTED,
            'period'       => StatisticsManager::ACPR_CALCULATION_PERIOD_MONTHS
        ];

        if (null !== $delay) {
            $delayQuery      = 'AND TIMESTAMPDIFF(MONTH, e.date_echeance, NOW()) >= :delay ';
            $params['delay'] = $delay;
        }

        $query = '
          SELECT
            l.id_project, 
            SUM(e.capital - e.capital_rembourse) / 100 AS amount
          FROM echeanciers e
            INNER JOIN loans l ON l.id_loan = e.id_loan
            INNER JOIN underlying_contract c ON c.id_contract = l.id_type_contract
          WHERE c.label = :contractType
            AND e.status != :repaid
            AND l.id_project IN
              (
                SELECT p.id_project
                FROM projects p
                  INNER JOIN echeanciers e ON e.id_project = p.id_project
                  INNER JOIN loans l ON e.id_loan = l.id_loan
                  INNER JOIN underlying_contract c ON c.id_contract = l.id_type_contract
                WHERE c.label = :contractType
                  AND e.status != :repaid
                  AND l.status = :accepted
                  AND p.status >= :problem
                  AND TIMESTAMPDIFF(MONTH, (SELECT added FROM projects_status_history
                        INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                        WHERE  projects_status.status = :repayment
                          AND e.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1),  NOW()) <= :period 
                  ' . $delayQuery . '
                GROUP BY p.id_project
              )
          GROUP BY l.id_project';

        $statement = $this->bdd->executeQuery($query, $params);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOwedCapitalByProjects($contractType)
    {
        $query = '  SELECT l.id_project, SUM(e.capital - e.capital_rembourse) / 100 AS amount
                    FROM echeanciers e
                      INNER JOIN loans l ON e.id_loan = l.id_loan
                      INNER JOIN underlying_contract c ON c.id_contract = l.id_type_contract
                    WHERE c.label = :contractType
                      AND e.status != :repaid
                      AND l.status = :accepted
                    GROUP BY l.id_project';

        $statement = $this->bdd->executeQuery(
            $query,
            ['contractType' => $contractType, 'repaid' => EcheanciersEntity::STATUS_REPAID, 'accepted' => Loans::STATUS_ACCEPTED]
        );
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
