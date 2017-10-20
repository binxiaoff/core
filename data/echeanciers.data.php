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

class echeanciers extends echeanciers_crud
{
    const STATUS_PENDING                  = 0;
    const STATUS_REPAID                   = 1;
    const STATUS_PARTIALLY_REPAID         = 2;
    const IS_NOT_EARLY_REFUND             = 0;
    const IS_EARLY_REFUND                 = 1;
    const STATUS_REPAYMENT_EMAIL_NOT_SENT = 0;
    const STATUS_REPAYMENT_EMAIL_SENT     = 1;

    public function __construct($bdd, $params = '')
    {
        parent::echeanciers($bdd, $params);
    }

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
     * @return string
     */
    public function getTotalAmount(array $selector)
    {
        return $this->getPartialSum('capital + interets', $selector);
    }

    /**
     * @param array $selector
     * @return string
     */
    public function getTotalInterests(array $selector)
    {
        return $this->getPartialSum('interets', $selector);
    }

    /**
     * @param array $selector
     * @return string
     */
    public function getOwedCapital(array $selector)
    {
        return $this->getPartialSum('capital - capital_rembourse', $selector, array(self::STATUS_PENDING, self::STATUS_PARTIALLY_REPAID));
    }

    /**
     * @param array $selector
     * @return string
     */
    public function getOwedInterests(array $selector)
    {
        return $this->getPartialSum('interets - interets_rembourses', $selector, array(self::STATUS_PENDING, self::STATUS_PARTIALLY_REPAID));
    }

    /**
     * @param int       $projectId
     * @param \DateTime $endDate
     * @return string
     */
    public function getUnpaidAmountAtDate($projectId, \DateTime $endDate)
    {
        $bind     = [
            'id_project'       => $projectId,
            'loan_status'      => \loans::STATUS_ACCEPTED,
            'repayment_status' => array(self::STATUS_PENDING, self::STATUS_PARTIALLY_REPAID),
            'date_echeance'    => $endDate->format('Y-m-d H:i:s')
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
              AND e.date_echeance < :date_echeance';
        return bcdiv($this->bdd->executeQuery($query, $bind, $bindType)
            ->fetchColumn(0), 100, 2);
    }

    /**
     * @param int $projectId
     * @param int $due
     * @return string
     * @throws Exception
     */
    public function getRemainingCapitalAtDue($projectId, $due)
    {
        $bind     = [
            'id_project'       => $projectId,
            'loan_status'      => \loans::STATUS_ACCEPTED,
            'repayment_status' => array(self::STATUS_PENDING, self::STATUS_PARTIALLY_REPAID),
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
     * @return string
     */
    public function getRepaidAmount(array $selector)
    {
        return bcadd($this->getRepaidCapital($selector), $this->getRepaidInterests($selector), 2);
    }

    /**
     * @param array $selector
     * @return string
     */
    public function getRepaidCapital(array $selector)
    {
        return $this->getPartialSum('capital_rembourse', $selector, array(self::STATUS_REPAID, self::STATUS_PARTIALLY_REPAID));
    }

    /**
     * @param array $selector
     * @return string
     */
    public function getRepaidInterests(array $selector)
    {
        return $this->getPartialSum('interets_rembourses', $selector, array(self::STATUS_REPAID, self::STATUS_PARTIALLY_REPAID), 0);
    }

    /**
     * @param string $amountType
     * @param array $selector
     * @param array $status
     * @param int|null $earlyRepaymentStatus
     * @return string
     */
    private function getPartialSum($amountType, array $selector, array $status = array(), $earlyRepaymentStatus = null)
    {
        $query = '
            SELECT SUM(' . $amountType . ')
            FROM echeanciers e
            INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE l.status = ' . \loans::STATUS_ACCEPTED;

        if (false === empty($selector)) {
            $query .=  ' AND e.' . $this->implodeSelector($selector);
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
     * @return array
     */
    public function getYearlySchedule(array $selector)
    {
        $result      = array();
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
     * @param int $lenderId
     * @param string $startDate
     * @param string $endDate
     * @return string
     */
    public function getNextRepaymentAmountInDateRange($lenderId, $startDate, $endDate)
    {
        return $this->getRepaymentAmountInDateRange($lenderId, $startDate, $endDate, 'e.capital + e.interets', [self::STATUS_PENDING]);
    }

    /**
     * @param int $projectId
     * @param DateTime $startDate
     * @return string
     * @throws Exception
     */
    public function getTotalComingCapitalByProject($projectId, DateTime $startDate = null)
    {
        if ($startDate === null) {
            $startDate = new DateTime();
        }
        $bind     = [
            'id_project'        => $projectId,
            'loan_status'      => \loans::STATUS_ACCEPTED,
            'repayment_status' => self::STATUS_PENDING,
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
     * @param int $lenderId
     * @param int $startDate
     * @param string $endDate
     * @param string $amountType
     * @param array $repaymentStatus
     * @param int|null $earlyRepayment
     * @param int|null $loanId
     * @return string
     * @throws Exception
     */
    private function getRepaymentAmountInDateRange($lenderId, $startDate, $endDate, $amountType, $repaymentStatus, $earlyRepayment = null, $loanId = null)
    {
        $bind     = [
            'loan_status'      => \loans::STATUS_ACCEPTED,
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

        if (in_array(self::STATUS_PENDING, $repaymentStatus)) {
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

        if (false === is_null($earlyRepayment)) {
            $bind['status_ra']     = $earlyRepayment;
            $bindType['status_ra'] = \PDO::PARAM_INT;
            $query .= ' AND e.status_ra = :status_ra ';
        }
        if (false === is_null($loanId)) {
            $bind['id_loan']     = $loanId;
            $bindType['id_loan'] = \PDO::PARAM_INT;
            $query .= ' AND l.id_loan = :id_loan ';
        }
        if (false === is_null($lenderId)) {
            $bind['id_lender']     = $lenderId;
            $bindType['id_lender'] = \PDO::PARAM_INT;
            $query .= ' AND e.id_lender = :id_lender ';
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
            LEFT JOIN echeanciers unpaid ON unpaid.id_echeancier = e.id_echeancier AND unpaid.status = ' . self::STATUS_PENDING . ' 
              AND DATEDIFF(NOW(), unpaid.date_echeance) > ' . \Unilend\Bundle\CoreBusinessBundle\Entity\UnilendStats::DAYS_AFTER_LAST_PROBLEM_STATUS_FOR_STATISTIC_LOSS . '
            INNER JOIN loans l ON l.id_lender = e.id_lender AND l.id_loan = e.id_loan
            INNER JOIN projects p ON p.id_project = e.id_project
            INNER JOIN companies c ON c.id_company = p.id_company
            INNER JOIN company_status cs ON cs.id = c.id_status
            WHERE e.status IN(' . self::STATUS_PENDING . ', ' . self::STATUS_PARTIALLY_REPAID . ')
                AND l.status = 0
                AND (cs.label != \'' . \Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus::STATUS_IN_BONIS . '\' OR unpaid.date_echeance IS NOT NULL)
                AND e.id_lender = :id_lender';

        return $this->bdd->executeQuery($sql, ['id_lender' => $lenderId])->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param int $projectId
     * @param int $ordre
     * @param string $annuler
     */
    public function updateStatusEmprunteur($projectId, $ordre, $annuler = '')
    {
        if ($annuler != '') {
            $sql = 'UPDATE echeanciers SET status_emprunteur = 0, date_echeance_emprunteur_reel = "0000-00-00 00:00:00", updated = "' . date('Y-m-d H:i:s') . '" WHERE id_project = ' . $projectId . ' AND ordre = ' . $ordre;
        } else {
            $sql = 'UPDATE echeanciers SET status_emprunteur = 1, date_echeance_emprunteur_reel = "' . date('Y-m-d H:i:s') . '", updated = "' . date('Y-m-d H:i:s') . '" WHERE id_project = ' . $projectId . ' AND ordre = ' . $ordre;
        }

        $this->bdd->query($sql);
    }

    // premiere echance emprunteur
    public function getPremiereEcheancePreteur($projectId, $id_lender)
    {
        // premiere echeance
        $PremiereEcheance = $this->select('ordre = 1 AND id_project = ' . $projectId . ' AND id_lender = ' . $id_lender, '', 0, 1);
        return $PremiereEcheance[0];
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
        $sql = 'UPDATE echeanciers SET date_echeance = "' . $date_echeance . '", date_echeance_emprunteur = "' . $date_echeance_emprunteur . '", updated = "' . date('Y-m-d H:i:s') . '" WHERE id_project = ' . $projectId . ' AND status_emprunteur = 0 AND ordre = "' . $ordre . '" ';
        $this->bdd->query($sql);
    }

    // Utilisé dans cron check remb preteurs (27/04/2015)
    public function selectEcheanciersByprojetEtOrdre()
    {
        $sql = '
            SELECT id_project,
                ordre,
                status,
                DATE(date_echeance) AS date_echeance,
                DATE(date_echeance_emprunteur) AS date_echeance_emprunteur,
                DATE(date_echeance_emprunteur_reel) AS date_echeance_emprunteur_reel,
                status_emprunteur
            FROM echeanciers
            WHERE DATE(date_echeance) = "' . date('Y-m-d') . '"
                AND status = ' . self::STATUS_PENDING . '
            GROUP BY id_project, ordre';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function getRepaymentOfTheDay(\DateTime $date)
    {
        $bind = ['formatedDate' => $date->format('Y-m-d')];
        $type = ['formatedDate' => \PDO::PARAM_STR];

        $sql = '
            SELECT id_project,
              ordre,
              COUNT(*) AS nb_repayment,
              COUNT(CASE status WHEN '. self::STATUS_REPAID .' THEN 1 ELSE NULL END) AS nb_repayment_paid
            FROM echeanciers
            WHERE DATE(date_echeance) = :formatedDate AND status_ra = '. self::IS_NOT_EARLY_REFUND .'
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
                AND id_lender = (SELECT id_lender FROM echeanciers where id_project = ' . $iProjectID . ' LIMIT 1)
            GROUP BY id_project
            ORDER BY ordre ASC
            LIMIT 1'
        );

        return $this->bdd->fetch_assoc($resultat);
    }



    /**
     * @param int $lenderId
     * @param int|null $projectId
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
            $sql .= ' AND e.id_project = :id_project';
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
                        WHERE  projects_status.status = '. \projects_status::REMBOURSEMENT .'
                          AND echeanciers.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                    FROM echeanciers
                        WHERE echeanciers.status IN (' . self::STATUS_REPAID . ', ' . self::STATUS_PARTIALLY_REPAID . ')
                    GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $contractType
     * @param int    $delay
     *
     * @return mixed
     */
    public function getProblematicOwedCapitalByProjects($contractType, $delay)
    {
        $query = '  SELECT l.id_project, SUM(e.capital - e.capital_rembourse) / 100 AS amount
                    FROM echeanciers e
                      INNER JOIN loans l ON l.id_loan = e.id_loan
                      INNER JOIN underlying_contract c ON c.id_contract = l.id_type_contract
                    WHERE c.label = :contractType
                      AND e.status != :repaid
                      AND l.id_project in
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
                            AND DATEDIFF(NOW(), e.date_echeance) >= :delay
                          GROUP BY p.id_project
                        )
                    GROUP BY l.id_project';
        $statement = $this->bdd->executeQuery(
            $query,
            ['problem' => projects_status::PROBLEME, 'contractType' => $contractType, 'repaid' => echeanciers::STATUS_REPAID, 'delay' => $delay, 'accepted' => loans::STATUS_ACCEPTED]
        );
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
            ['contractType' => $contractType, 'repaid' => echeanciers::STATUS_REPAID, 'accepted' => loans::STATUS_ACCEPTED]
        );
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
