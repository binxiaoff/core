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

use \Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\TaxType;

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
    public function getTotalCapital(array $selector)
    {
        return $this->getPartialSum('capital', $selector);
    }

    /**
     * @param array $selector
     * @return string
     */
    public function getOwedAmount(array $selector)
    {
        return $this->getPartialSum('capital - capital_rembourse + interets - interets_rembourses', $selector, array(self::STATUS_PENDING, self::STATUS_PARTIALLY_REPAID));
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
     * number of remaining periods
     * @param int $id_lender
     * @param int $id_project
     * @return int
     */
    public function counterPeriodRestantes($id_lender, $id_project)
    {
        $sql = 'SELECT count(DISTINCT(ordre)) FROM `echeanciers` WHERE id_lender = ' . $id_lender . ' AND id_project = ' . $id_project . ' AND status = ' . self::STATUS_PENDING;

        $result = $this->bdd->query($sql);
        return (int) $this->bdd->result($result);
    }

    /**
     * @param int $lenderId
     * @param string $startDate
     * @param string $endDate
     * @param int $loanId
     * @return string
     */
    public function getRepaidAmountInDateRange($lenderId, $startDate, $endDate, $loanId = null)
    {
        return $this->getRepaymentAmountInDateRange($lenderId, $startDate, $endDate, 'e.capital_rembourse + e.interets_rembourses', [self::STATUS_PARTIALLY_REPAID, self::STATUS_REPAID], null, $loanId);
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

    public function getNonRepaidAmountInDateRange($lenderId, DateTime $startDate, DateTime $endDate, $loanId = null)
    {
        return $this->getRepaymentAmountInDateRange($lenderId, $startDate->format('Y-m-d H:i:s'), $endDate->format('Y-m-d H:i:s'), 'e.capital - e.capital_rembourse + e.interets - e.interets_rembourses', [self::STATUS_PENDING, self::STATUS_PARTIALLY_REPAID], null, $loanId);
    }

    /**
     * @param int $lenderId
     * @param int $loanId
     * @param DateTime $startDate
     * @return string
     * @throws Exception
     */
    public function getTotalComingCapital($lenderId, $loanId, DateTime $startDate = null)
    {
        if ($startDate === null) {
            $startDate = new DateTime();
        }
        $bind     = [
            'id_lender'        => $lenderId,
            'loan_status'      => \loans::STATUS_ACCEPTED,
            'id_loan'          => $loanId,
            'repayment_status' => self::STATUS_PENDING,
            'date_echeance'    => $startDate->format('Y-m-d')
        ];
        $bindType = [
            'id_lender'        => \PDO::PARAM_INT,
            'loan_status'      => \PDO::PARAM_INT,
            'id_loan'          => \PDO::PARAM_INT,
            'repayment_status' => \PDO::PARAM_INT,
            'date_echeance'    => \PDO::PARAM_STR
        ];
        $query    = '
            SELECT SUM(e.capital)
            FROM echeanciers e
            INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE l.status = :loan_status
              AND e.id_lender = :id_lender
              AND e.id_loan = :id_loan
              AND e.status = :repayment_status
              AND date(e.date_echeance) > :date_echeance';
        return bcdiv($this->bdd->executeQuery($query, $bind, $bindType)
            ->fetchColumn(0), 100, 2);
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
            LEFT JOIN echeanciers unpaid ON unpaid.id_echeancier = e.id_echeancier AND unpaid.status = ' . self::STATUS_PENDING . ' AND DATEDIFF(NOW(), unpaid.date_echeance) > 180
            INNER JOIN loans l ON l.id_lender = e.id_lender AND l.id_loan = e.id_loan
            WHERE e.status IN(' . self::STATUS_PENDING . ', ' . self::STATUS_PARTIALLY_REPAID . ')
                AND l.status = 0
                AND (
                    (SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON ps.id_project_status = psh.id_project_status WHERE psh.id_project = e.id_project ORDER BY psh.added DESC, psh.id_project_status_history DESC LIMIT 1) >= ' . \projects_status::PROCEDURE_SAUVEGARDE . '
                    OR unpaid.date_echeance IS NOT NULL
                )
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

    public function getTotalRepaidInterestByCohort()
    {
        $query = 'SELECT
                      SUM(interets_rembourses)/100 AS amount,
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
                          AND echeanciers.id_project = projects_status_history.id_project
                        ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                      ) AS cohort
                    FROM echeanciers
                        WHERE echeanciers.status IN (' . self::STATUS_REPAID . ', ' . self::STATUS_PARTIALLY_REPAID . ')
                    GROUP BY cohort';

        $statement = $this->bdd->executeQuery($query);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

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

    /**
     * Returns capital, interests and tax sum amounts grouped by month, quarter and year for a lender
     * takes into account regular past payments at their real date
     * recovery payments including commission
     * future payments of healthy (according to stats definition) only projects
     * @param int $lenderId
     *
     * @return array
     */
    public function getDataForRepaymentWidget($lenderId)
    {
        $bind  = [
            'id_lender'                    => $lenderId,
            'tax_type_exempted_lender'     => \Unilend\Bundle\CoreBusinessBundle\Service\TaxManager::TAX_TYPE_EXEMPTED_LENDER,
            'tax_type_taxable_lender'      => \Unilend\Bundle\CoreBusinessBundle\Service\TaxManager::TAX_TYPE_TAXABLE_LENDER,
            'tax_type_foreigner_lender'    => \Unilend\Bundle\CoreBusinessBundle\Service\TaxManager::TAX_TYPE_FOREIGNER_LENDER,
            'tax_type_legal_entity_lender' => \Unilend\Bundle\CoreBusinessBundle\Service\TaxManager::TAX_TYPE_LEGAL_ENTITY_LENDER
        ];
        $type  = [
            'id_lender'                    => \PDO::PARAM_INT,
            'tax_type_exempted_lender'     => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'tax_type_taxable_lender'      => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'tax_type_foreigner_lender'    => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'tax_type_legal_entity_lender' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
        ];
        $query = '
            SELECT
                t.month                          AS month,
                t.quarter                        AS quarter,
                t.year                           AS year,
                ROUND(SUM(t.capital), 2)         AS capital,
                ROUND(SUM(t.grossInterests), 2)  AS grossInterests,
                ROUND(SUM(t.repaidTaxes), 2)     AS repaidTaxes,
                ROUND(SUM(t.upcomingTaxes), 2)   AS upcomingTaxes
            FROM (
                SELECT
                  LEFT(o_capital.added, 7)     AS month,
                  QUARTER(o_capital.added)     AS quarter,
                  YEAR(o_capital.added)        AS year,
                  SUM(o_capital.amount)        AS capital,
                  SUM(o_interest.amount)       AS grossInterests,
                  SUM((SELECT SUM(amount)
                       FROM operation o_taxes
                        INNER JOIN operation_type ot_taxes ON o_taxes.id_type = ot_taxes.id AND ot_taxes.label IN ("' . implode('","', OperationType::TAX_TYPES_FR) . '") 
                    WHERE o_taxes.id_repayment_schedule = o_interest.id_repayment_schedule)) AS repaidTaxes,
                  0                          AS upcomingTaxes
                FROM operation o_capital
                  INNER JOIN operation_type ot_capital ON o_capital.id_type = ot_capital.id AND ot_capital.label = "' . OperationType::CAPITAL_REPAYMENT . '"
                  LEFT JOIN operation o_interest ON o_capital.id_repayment_schedule = o_interest.id_repayment_schedule AND o_interest.id_type = (SELECT id FROM operation_type ot_interest WHERE ot_interest.label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '")
                WHERE o_capital.id_wallet_creditor = :id_lender
                GROUP BY year, quarter, month

                UNION ALL

                (SELECT
                    LEFT(e.date_echeance, 7)        AS month,
                    QUARTER(e.date_echeance)        AS quarter,
                    YEAR(e.date_echeance)           AS year,
                    ROUND(SUM(e.capital) / 100, 2)  AS capital,
                    ROUND(SUM(e.interets) / 100, 2) AS grossInterests,
                    0                               AS repaidTaxes,
                    CASE c.type
                        -- Natural person
                        WHEN ' . Clients::TYPE_PERSON . ' OR ' . Clients::TYPE_PERSON_FOREIGNER . ' THEN
                            CASE lih.resident_etranger
                                -- FR fiscal resident
                                WHEN 0 THEN 
                                    IF (
                                        lte.id_lender IS NULL,
                                        SUM(ROUND((e.interets - e.interets_rembourses) * (SELECT SUM(tt.rate / 100) FROM tax_type tt WHERE tt.id_tax_type IN (:tax_type_taxable_lender)) / 100, 2)),
                                        SUM(ROUND((e.interets - e.interets_rembourses) * (SELECT SUM(tt.rate / 100) FROM tax_type tt WHERE tt.id_tax_type IN (:tax_type_exempted_lender)) / 100, 2))
                                    )
                                -- Foreigner fiscal resident
                                WHEN 1 THEN
                                    SUM(ROUND((e.interets - e.interets_rembourses) * (SELECT tt.rate / 100 FROM tax_type tt WHERE tt.id_tax_type IN (:tax_type_foreigner_lender)) / 100, 2))
                            END
                        -- Legal entity
                        WHEN ' . Clients::TYPE_LEGAL_ENTITY . ' OR ' . Clients::TYPE_LEGAL_ENTITY_FOREIGNER . ' THEN
                            SUM(ROUND((e.interets - e.interets_rembourses) * (SELECT tt.rate / 100 FROM tax_type tt WHERE tt.id_tax_type IN (:tax_type_legal_entity_lender)) / 100, 2))
                    END                           AS upcomingTaxes
                FROM echeanciers e
                INNER JOIN wallet w ON e.id_lender = w.id
                LEFT JOIN clients c ON w.id_client = c.id_client
                LEFT JOIN lender_tax_exemption lte ON lte.id_lender = e.id_lender AND lte.year = YEAR(e.date_echeance)
                LEFT JOIN lenders_imposition_history lih ON lih.id_lenders_imposition_history = (SELECT MAX(id_lenders_imposition_history) FROM lenders_imposition_history WHERE id_lender = e.id_lender)
                LEFT JOIN projects p ON e.id_project = p.id_project
                WHERE e.id_lender = :id_lender
                    AND e.status = 0
                    AND e.date_echeance >= NOW()
                    AND IF(
                        (p.status IN (' . implode(',', [\projects_status::PROCEDURE_SAUVEGARDE, \projects_status::REDRESSEMENT_JUDICIAIRE, \projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::DEFAUT]) . ')
                        OR (p.status >= ' . \projects_status::PROBLEME . '
                        AND DATEDIFF(NOW(), (
                        SELECT psh2.added
                        FROM projects_status_history psh2
                        INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                        WHERE ps2.status = ' . \projects_status::PROBLEME . '
                        AND psh2.id_project = e.id_project
                        ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                        LIMIT 1
                    )) > 180)), TRUE, FALSE) = FALSE
                GROUP BY year, quarter, month)
            ) AS t
            GROUP BY t.year, t.quarter, t.month';

        /** @var \Doctrine\DBAL\Cache\QueryCacheProfile $oQCProfile */
        $oQCProfile = new \Doctrine\DBAL\Cache\QueryCacheProfile(\Unilend\librairies\CacheKeys::DAY, md5(__METHOD__));
        /** @var \Doctrine\DBAL\Statement $statement */
        $statement = $this->bdd->executeQuery($query, $bind, $type, $oQCProfile);
        $data      = [];

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $taxes = bcadd($row['repaidTaxes'], $row['upcomingTaxes'], 2);
            unset($row['repaidTaxes'], $row['upcomingTaxes']);

            $row['netInterests']   = (float) bcsub($row['grossInterests'], $taxes, 2);
            $row['taxes']          = (float) $taxes;
            $row['capital']        = (float) $row['capital'];
            $data[$row['month']]   = $row;
        }
        $statement->closeCursor();

        return $data;
    }
}
