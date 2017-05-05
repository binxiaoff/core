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
    public function getEarlyRepaidCapital(array $selector)
    {
        return $this->getPartialSum('capital_rembourse', $selector, array(self::STATUS_REPAID, self::STATUS_PARTIALLY_REPAID), 1);
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
     * @param int $startDate
     * @param int $endDate
     * @return string
     */
    public function getRepaidCapitalInDateRange($lenderId, $startDate, $endDate)
    {
        return $this->getRepaymentAmountInDateRange($lenderId, $startDate, $endDate, 'e.capital_rembourse', [self::STATUS_PARTIALLY_REPAID, self::STATUS_REPAID]);
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
     * @return array
     */
    public function getMonthlyScheduleByProject($projectId)
    {
        $sql = '
            SELECT ordre,
                SUM(montant) AS montant,
                SUM(capital) AS capital,
                SUM(interets) AS interets,
                status_emprunteur
            FROM echeanciers
            WHERE id_project = :id_project 
            GROUP BY ordre';

        $res       = [];
        $statement = $this->bdd->executeQuery($sql,
            array('id_project' => $projectId),
            array('id_project' => \PDO::PARAM_INT),
            new \Doctrine\DBAL\Cache\QueryCacheProfile(\Unilend\librairies\CacheKeys::SHORT_TIME, md5(__METHOD__)));
        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        foreach ($result as $key => $aRow) {
            $res[$aRow['ordre']] = array(
                'montant'           => bcdiv($aRow['montant'], 100, 2),
                'capital'           => bcdiv($aRow['capital'], 100, 2),
                'interets'          => bcdiv($aRow['interets'], 100, 2),
                'status_emprunteur' => $aRow['status_emprunteur']
            );
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
     * @param int $iLoanId
     * @param int $iAnticipatedRepaymentStatus
     * @param string $sOrder
     * @return array
     */
    public function getRepaymentWithTaxDetails($iLoanId, $iAnticipatedRepaymentStatus = 0, $sOrder = 'e.ordre ASC')
    {
        $sql = '
            SELECT e.*, SUM(IFNULL(tax.amount, 0)) AS tax
            FROM echeanciers e
                LEFT JOIN transactions t ON e.id_echeancier = t.id_echeancier AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
                LEFT JOIN tax ON t.id_transaction = tax.id_transaction
            WHERE e.id_loan = ' . $iLoanId . ' AND e.status_ra = ' . $iAnticipatedRepaymentStatus . '
            GROUP BY e.id_echeancier
            ORDER BY ' . $sOrder;

        $result  = $this->bdd->query($sql);
        $aReturn = array();
        while ($record = $this->bdd->fetch_assoc($result)) {
            $aReturn[] = $record;
        }
        return $aReturn;
    }

    /**
     * @param DateTime $date
     * @return array|null
     * @throws Exception
     */
    public function getTaxState($date)
    {
        $sql = '
            SELECT
              c.id_client,
              e.id_lender,
              c.type,
              (
                SELECT p.iso
                FROM lenders_imposition_history lih
                  JOIN pays_v2 p ON p.id_pays = lih.id_pays
                WHERE lih.added <= e.date_echeance_reel
                      AND lih.id_lender = e.id_lender
                ORDER BY lih.added DESC
                LIMIT 1
              ) AS iso_pays,
              /*if the lender is FR resident and it is a physical person then it is not taxed at source : taxed_at_source = 0*/
              CASE
                  IFNULL((SELECT resident_etranger
                     FROM lenders_imposition_history lih
                     WHERE lih.id_lender = w.id AND lih.added <= e.date_echeance_reel
                     ORDER BY added DESC
                     LIMIT 1
                  ), 0) = 0 AND 1 = c.type
                  WHEN TRUE
                    THEN 0
                  ELSE 1
              END AS taxed_at_source,
              CASE
                  WHEN lte.year IS NULL THEN
                      0
                  ELSE
                      1
              END AS exonere,
              (SELECT group_concat(lte.year SEPARATOR ", ")
               FROM lender_tax_exemption lte
               WHERE lte.id_lender = w.id) AS annees_exoneration,
              e.id_project,
              e.id_loan,
              l.id_type_contract,
              e.ordre,
              REPLACE(e.montant, ".", ","),
              REPLACE(e.capital_rembourse, ".", ","),
              REPLACE(e.interets_rembourses, ".", ","),
              REPLACE(ROUND(prelevements_obligatoires.amount / 100, 2), ".", ","),
              REPLACE(ROUND(retenues_source.amount / 100, 2), ".", ","),
              REPLACE(ROUND(csg.amount / 100, 2), ".", ","),
              REPLACE(ROUND(prelevements_sociaux.amount / 100, 2), ".", ","),
              REPLACE(ROUND(contributions_additionnelles.amount / 100, 2), ".", ","),
              REPLACE(ROUND(prelevements_solidarite.amount / 100, 2), ".", ","),
              REPLACE(ROUND(crds.amount / 100, 2), ".", ","),
              e.date_echeance,
              e.date_echeance_reel,
              e.status,
              e.date_echeance_emprunteur,
              e.date_echeance_emprunteur_reel
            FROM echeanciers e
              INNER JOIN loans l ON l.id_loan = e.id_loan
              INNER JOIN wallet w ON w.id = e.id_lender
              INNER JOIN clients c ON c.id_client = w.id_client
              LEFT JOIN lender_tax_exemption lte ON lte.id_lender = e.id_lender AND lte.year = YEAR(e.date_echeance_reel)
              INNER JOIN transactions t ON t.id_echeancier = e.id_echeancier AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
              LEFT JOIN tax prelevements_obligatoires ON prelevements_obligatoires.id_transaction = t.id_transaction AND prelevements_obligatoires.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX . '
              LEFT JOIN tax retenues_source ON retenues_source.id_transaction = t.id_transaction AND retenues_source.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE . '
              LEFT JOIN tax csg ON csg.id_transaction = t.id_transaction AND csg.id_tax_type = ' . \tax_type::TYPE_CSG . '
              LEFT JOIN tax prelevements_sociaux ON prelevements_sociaux.id_transaction = t.id_transaction AND prelevements_sociaux.id_tax_type = ' . \tax_type::TYPE_SOCIAL_DEDUCTIONS . '
              LEFT JOIN tax contributions_additionnelles ON contributions_additionnelles.id_transaction = t.id_transaction AND contributions_additionnelles.id_tax_type = ' . \tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS . '
              LEFT JOIN tax prelevements_solidarite ON prelevements_solidarite.id_transaction = t.id_transaction AND prelevements_solidarite.id_tax_type = ' . \tax_type::TYPE_SOLIDARITY_DEDUCTIONS . '
              LEFT JOIN tax crds ON crds.id_transaction = t.id_transaction AND crds.id_tax_type = ' . \tax_type::TYPE_CRDS . '
            WHERE e.date_echeance_reel BETWEEN :startDate AND :endDate
                AND e.status IN (' . self::STATUS_REPAID . ', ' . self::STATUS_PARTIALLY_REPAID . ')
                AND e.status_ra = 0
            ORDER BY e.date_echeance ASC';

        return $this->bdd->executeQuery(
            $sql,
            ['startDate' => $date->format('Y-m-d 00:00:00'), 'endDate' => $date->format('Y-m-d 23:59:59')],
            ['startDate' => \PDO::PARAM_STR, 'endDate' => \PDO::PARAM_STR]
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param array $taxType
     * @return array
     * @throws Exception
     */
    public function getFiscalState(\DateTime $startDate, \DateTime $endDate, array $taxType)
    {
        $taxDynamicJoin = $this->getDynamicTaxJoins($taxType);
        $aBind          = [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date'   => $endDate->format('Y-m-d'),
        ];
        $aType          = [
            'start_date' => \PDO::PARAM_STR,
            'end_date'   => \PDO::PARAM_STR,
        ];

        $sql = '
            SELECT
                l.id_type_contract,
                CASE c.type
                    WHEN ' . Clients::TYPE_LEGAL_ENTITY . ' THEN "legal_entity"
                    WHEN ' . Clients::TYPE_PERSON . ' OR ' . Clients::TYPE_PERSON_FOREIGNER . ' THEN "person"
                END AS client_type,
                CASE IFNULL(
                    (SELECT resident_etranger
                        FROM lenders_imposition_history lih
                        WHERE lih.id_lender = w.id AND lih.added <= e.date_echeance_reel
                        ORDER BY added DESC
                        LIMIT 1), 0
                    )
                    WHEN 0 THEN "fr"
                    ELSE "ww"
                END AS fiscal_residence,
                CASE lte.id_lender
                    WHEN e.id_lender THEN "non_taxable"
                    ELSE "taxable"
                END AS exemption_status,
                ' . $taxDynamicJoin['tax_columns'] . '
                SUM(ROUND(e.interets_rembourses / 100, 2)) AS interests
            FROM echeanciers e
              INNER JOIN loans l ON l.id_loan = e.id_loan AND l.status = 0
              INNER JOIN wallet w ON l.id_lender = w.id
              INNER JOIN clients c ON w.id_client = c.id_client
              INNER JOIN transactions t ON t.id_echeancier = e.id_echeancier AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
              ' . $taxDynamicJoin['tax_join'] . '
              LEFT JOIN lender_tax_exemption lte ON lte.id_lender = w.id AND lte.year = YEAR(e.date_echeance_reel)
            WHERE e.status IN (' . self::STATUS_REPAID . ', ' . self::STATUS_PARTIALLY_REPAID . ')
                AND e.status_ra = 0
                AND DATE(e.date_echeance_reel) BETWEEN :start_date AND :end_date
            GROUP BY l.id_type_contract, client_type, fiscal_residence, exemption_status';

        $statement = $this->bdd->executeQuery($sql, $aBind, $aType, new \Doctrine\DBAL\Cache\QueryCacheProfile(\Unilend\librairies\CacheKeys::LONG_TIME, md5(__METHOD__)));
        $result    = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $result;
    }

    /**
     * @param array $taxType
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array $clientType
     * @return array
     */
    public function getRepaymentForNonExemptedInDateRange(array $taxType, \DateTime $startDate, \DateTime $endDate, array $clientType)
    {
        return $this->getRepaymentsBetweenDate($taxType, $startDate, $endDate, $clientType, 0);
    }

    /**
     * @param array $taxType
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array $clientType
     * @return array
     */
    public function getRepaymentForExemptedInDateRange(array $taxType, \DateTime $startDate, \DateTime $endDate, array $clientType)
    {
        return $this->getRepaymentsBetweenDate($taxType, $startDate, $endDate, $clientType, 1);
    }

    /**
     * @param array $taxType
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return array
     */
    public function getForeignersRepaymentsInDateRange(array $taxType, \DateTime $startDate, \DateTime $endDate)
    {
        return $this->getRepaymentsBetweenDate($taxType, $startDate, $endDate, [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER], 2, true);
    }

    /**
     * @param array $taxType
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array $clientType
     * @param int $exempted
     * @param boolean $foreigner
     * @return array
     * @throws Exception
     */
    private function getRepaymentsBetweenDate(array $taxType, \DateTime $startDate, \DateTime $endDate, array $clientType, $exempted, $foreigner = false)
    {
        $taxDynamicJoin  = $this->getDynamicTaxJoins($taxType);
        $aBind           = [
            'start_date'  => $startDate->format('Y-m-d 00:00:00'),
            'end_date'    => $endDate->format('Y-m-d 23:59:59'),
            'client_type' => $clientType
        ];
        $aType           = [
            'start_date'  => \PDO::PARAM_STR,
            'end_date'    => \PDO::PARAM_STR,
            'client_type' => \Unilend\Bridge\Doctrine\DBAL\Connection::PARAM_INT_ARRAY
        ];

        $sExemptionWhere = '';

        switch ($exempted) {
            case 0:
                $taxExemptionJoin = ' LEFT JOIN lender_tax_exemption lte ON lte.id_lender = w.id AND lte.year = YEAR(e.date_echeance_reel) ';
                $sExemptionWhere  = 'AND lte.id_lender IS NULL';
                break;
            case 1:
                $taxExemptionJoin = ' INNER JOIN lender_tax_exemption lte ON lte.id_lender = w.id AND lte.year = YEAR(e.date_echeance_reel) ';
                break;
            default:
                $taxExemptionJoin = ' LEFT JOIN lender_tax_exemption lte ON lte.id_lender = w.id ';
                break;
        }

        if (true === $foreigner) {
            $sForeignerWhere = '
            AND (SELECT resident_etranger FROM lenders_imposition_history lih WHERE lih.id_lender = w.id AND lih.added <= e.date_echeance_reel ORDER BY added DESC LIMIT 1) > 0 ';
        } else {
            $sForeignerWhere = '';
        }

        $sql = '
        SELECT
            l.id_type_contract,
            SUM(e.montant) AS montant,
            SUM(e.capital_rembourse) AS capital,
            ' . $taxDynamicJoin['tax_columns'] . '
            SUM(e.interets_rembourses) AS interets
        FROM echeanciers e
            INNER JOIN loans l ON l.id_loan = e.id_loan AND l.status = ' . \loans::STATUS_ACCEPTED . '
            INNER JOIN wallet w ON l.id_lender = w.id
            INNER JOIN clients c ON w.id_client = c.id_client
            INNER JOIN transactions t ON t.id_echeancier = e.id_echeancier AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS
            . $taxDynamicJoin['tax_join']
            . $taxExemptionJoin . '
        WHERE e.status IN (' . self::STATUS_REPAID . ', ' . self::STATUS_PARTIALLY_REPAID . ')
            AND e.status_ra = 0
            AND c.type IN (:client_type) '
            . $sExemptionWhere . $sForeignerWhere . '
            AND DATE(date_echeance_reel) BETWEEN :start_date AND :end_date
            GROUP BY l.id_type_contract';

        $statement = $this->bdd->executeQuery($sql, $aBind, $aType, new \Doctrine\DBAL\Cache\QueryCacheProfile(\Unilend\librairies\CacheKeys::LONG_TIME, md5(__METHOD__)));
        $result    = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $result;
    }

    /**
     * @param array $taxType array of id_tax_type to use in the query
     * @return array
     */
    private function getDynamicTaxJoins(array $taxType)
    {
        $taxColumns = '';
        $taxJoin    = '';

        foreach ($taxType as $row) {
            $taxName = 'tax_' . $row['id_tax_type'];
            $taxColumns .= ' ROUND(SUM(' . $taxName . '.amount) / 100, 2) AS ' . $taxName . ', ';
            $taxJoin    .= ' LEFT JOIN tax ' . $taxName . ' ON ' . $taxName . '.id_transaction = t.id_transaction AND ' . $taxName . '.id_tax_type = ' . $row['id_tax_type'];
        }

        return ['tax_join' => $taxJoin, 'tax_columns' => $taxColumns];
    }

    /**
     * @param int   $clientId
     * @param int   $year
     * @param array $projectIds
     * @return string
     */
    public function getLenderOwedCapital($clientId, $year, array $projectIds)
    {
        $sql = '
            SELECT SUM(capital - capital_rembourse)
            FROM echeanciers
              INNER JOIN wallet w ON w.id = echeanciers.id_lender
            WHERE (date_echeance_reel >= "' . $year . '-01-01" OR echeanciers.status IN (' . self::STATUS_PENDING . ', ' . self::STATUS_PARTIALLY_REPAID . '))
                AND id_project IN (' . implode(',', $projectIds) . ')
                AND w.id_client = ' . $clientId;

        return bcdiv($this->bdd->executeQuery($sql)->fetchColumn(0), 100, 2);
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
     * recovery payments including commission (fixed value, as done in 'declaration de créances')
     * future payments of healthy (according to stats definition) only projects
     * @param int $lenderId
     * @return array
     */
    public function getDataForRepaymentWidget($lenderId)
    {
        $taxTypeForExemptedLender    = [
            \tax_type::TYPE_CSG,
            \tax_type::TYPE_SOCIAL_DEDUCTIONS,
            \tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS,
            \tax_type::TYPE_SOLIDARITY_DEDUCTIONS,
            \tax_type::TYPE_CRDS
        ];
        $taxTypeForTaxableLender     = [
            \tax_type::TYPE_INCOME_TAX,
            \tax_type::TYPE_CSG,
            \tax_type::TYPE_SOCIAL_DEDUCTIONS,
            \tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS,
            \tax_type::TYPE_SOLIDARITY_DEDUCTIONS,
            \tax_type::TYPE_CRDS
        ];
        $taxTypeForForeignerLender   = [\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE];
        $taxTypeForLegalEntityLender = [\tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE];

        $bind  = [
            'id_lender'                    => $lenderId,
            'tax_type_exempted_lender'     => $taxTypeForExemptedLender,
            'tax_type_taxable_lender'      => $taxTypeForTaxableLender,
            'tax_type_foreigner_lender'    => $taxTypeForForeignerLender,
            'tax_type_legal_entity_lender' => $taxTypeForLegalEntityLender
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
                t.month                        AS month,
                t.quarter                      AS quarter,
                t.year                         AS year,
                ROUND(SUM(t.capital), 2)       AS capital,
                ROUND(SUM(t.grossInterests), 2)  AS grossInterests,
                ROUND(SUM(t.netInterests), 2)  AS netInterests,
                ROUND(SUM(t.repaidTaxes), 2)   AS repaidTaxes,
                ROUND(SUM(t.upcomingTaxes), 2) AS upcomingTaxes
            FROM (
                SELECT
                    LEFT(t_capital.date_transaction, 7)                                                                                AS month,
                    QUARTER(t_capital.date_transaction)                                                                                AS quarter,
                    YEAR(t_capital.date_transaction)                                                                                   AS year,
                    ROUND(SUM(t_capital.montant) / 100, 2)                                                                             AS capital,
                    NULL                                                                                                               AS grossInterests,
                    ROUND(SUM(t_interest.montant) / 100, 2)                                                                            AS netInterests,
                    SUM((SELECT ROUND(IFNULL(SUM(tax.amount), 0) / 100, 2) FROM tax WHERE id_transaction = t_interest.id_transaction)) AS repaidTaxes,
                    0                                                                                                                  AS upcomingTaxes
                FROM wallet w
                INNER JOIN transactions t_capital ON t_capital.id_client = w.id_client AND t_capital.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL . '
                LEFT JOIN transactions t_interest ON t_interest.id_echeancier = t_capital.id_echeancier AND t_interest.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
                WHERE w.id = :id_lender 
                GROUP BY year, quarter, month

                UNION ALL

                SELECT
                    LEFT(t.date_transaction, 7)            AS month,
                    QUARTER(t.date_transaction)            AS quarter,
                    YEAR(t.date_transaction)               AS year,
                    ROUND(SUM(t.montant) / 100 / 0.844, 2) AS capital,
                    0                                      AS grossInterests,
                    NULL                                   AS netInterests,
                    0                                      AS repaidTaxes,
                    0                                      AS upcomingTaxes
                FROM wallet w
                INNER JOIN transactions t ON t.id_client = w.id_client AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_RECOVERY_REPAYMENT . '
                WHERE w.id = :id_lender
                GROUP BY year, quarter, month

                UNION ALL

                (SELECT
                    LEFT(e.date_echeance, 7)        AS month,
                    QUARTER(e.date_echeance)        AS quarter,
                    YEAR(e.date_echeance)           AS year,
                    ROUND(SUM(e.capital) / 100, 2)  AS capital,
                    ROUND(SUM(e.interets) / 100, 2) AS grossInterests,
                    NULL                            AS netInterests,
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
            if (null !== $row['grossInterests'] && null !== $row['netInterests']) {
                $netInterest   = bcadd(bcsub($row['grossInterests'], $row['upcomingTaxes'], 2), $row['netInterests'], 2);
                $grossInterest = bcadd(bcadd($row['netInterests'], $row['repaidTaxes'], 2), $row['grossInterests'], 2);
            } else {
                $netInterest   = null === $row['netInterests'] ? bcsub($row['grossInterests'], $row['upcomingTaxes'], 2) : $row['netInterests'];
                $grossInterest = null === $row['grossInterests'] ? bcadd($row['netInterests'], $row['repaidTaxes'], 2) : $row['grossInterests'];
            }
            $taxes = bcadd($row['repaidTaxes'], $row['upcomingTaxes'], 2);
            unset($row['repaidTaxes'], $row['upcomingTaxes']);

            $row['grossInterests'] = (float) $grossInterest;
            $row['netInterests']   = (float) $netInterest;
            $row['taxes']          = (float) $taxes;
            $row['capital']        = (float) $row['capital'];
            $data[$row['month']]   = $row;
        }
        $statement->closeCursor();
        return $data;
    }

    public function getRepaidRepaymentToNotify($offset = 0, $limit = 100)
    {
        $query = 'SELECT e.*
                  FROM echeanciers e
                  INNER join transactions t ON t.id_echeancier = e.id_echeancier
                  WHERE e.status = :repaid
                  AND e.status_email_remb = :not_sent
                  GROUP BY e.id_echeancier
                  LIMIT :limit OFFSET :offset';

        $bind = [
            'repaid'   => self::STATUS_REPAID,
            'not_sent' => self::STATUS_REPAYMENT_EMAIL_NOT_SENT,
            'limit'    => $limit,
            'offset'   => $offset,
        ];

        $type = [
            'limit' => \PDO::PARAM_INT,
            'offset' => \PDO::PARAM_INT,
        ];
        /** @var \Doctrine\DBAL\Statement $statement */
        $statement = $this->bdd->executeQuery($query, $bind, $type);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

}
