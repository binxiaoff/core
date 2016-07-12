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
    const STATUS_PENDING          = 0;
    const STATUS_REPAID           = 1;
    const STATUS_PARTIALLY_REPAID = 2;

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

    public function getNonPaidRepaymentAmount($projectId, $endDate)
    {
        return $this->getNonPaidAmountAtDate($projectId, $endDate, 'e.capital - e.capital_rembourse + e.interets - e.interets_rembourses');
    }

    /**
     * @param int $projectId
     * @param string $endDate
     * @param string $amountType
     * @return string
     * @throws Exception
     */
    private function getNonPaidAmountAtDate($projectId, $endDate, $amountType)
    {
        $bind     = [
            'id_project'       => $projectId,
            'loan_status'      => \loans::STATUS_ACCEPTED,
            'repayment_status' => array(self::STATUS_PENDING, self::STATUS_PARTIALLY_REPAID),
            'date_echeance'    => $endDate
        ];
        $bindType = [
            'id_project'       => \PDO::PARAM_INT,
            'loan_status'      => \PDO::PARAM_INT,
            'repayment_status' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            'date_echeance'    => \PDO::PARAM_STR
        ];
        $query    = '
            SELECT SUM(' . $amountType . ')
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
     * @param string $ordre
     * @return string
     * @throws Exception
     */
    public function getRemainingCapital($projectId, $ordre)
    {
        $bind     = [
            'id_project'       => $projectId,
            'loan_status'      => \loans::STATUS_ACCEPTED,
            'repayment_status' => array(self::STATUS_PENDING, self::STATUS_PARTIALLY_REPAID),
            'ordre'            => $ordre
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
        return bcdiv($this->bdd->executeQuery($query, $bind, $bindType)
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
            SELECT SUM(e.' . $amountType . ')
            FROM echeanciers e
            INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE l.status = ' . \loans::STATUS_ACCEPTED . ' AND e.' . $this->implodeSelector($selector);

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
     * @param int $year
     * @return array
     */
    public function getMonthlyScheduleByYear(array $selector, $year)
    {
        $result      = array();
        $queryResult = $this->bdd->query('
            SELECT MONTH(date_echeance_reel) AS mois,
                ROUND(SUM(capital) / 100, 2) AS capital,
                ROUND(SUM(interets) / 100, 2) AS interets
            FROM echeanciers
            WHERE YEAR(date_echeance_reel) = ' . $year . ' AND ' . $this->implodeSelector($selector, array(' = ')) . '
            GROUP BY mois'
        );

        while ($record = $this->bdd->fetch_assoc($queryResult)) {
            $result[$record['mois']] = $record;
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
        return $this->getRepaymentAmountInDateRange($lenderId, $startDate, $endDate, 'e.capital_rembourse', array(\echeanciers::STATUS_PARTIALLY_REPAID, \echeanciers::STATUS_REPAID));
    }

    /**
     * @param int $lenderId
     * @param int $startDate
     * @param int $endDate
     * @return string
     */
    public function getRepaidInterestsInDateRange($lenderId, $startDate, $endDate)
    {
        return $this->getRepaymentAmountInDateRange($lenderId, $startDate, $endDate, 'e.interets_rembourses', array(\echeanciers::STATUS_PARTIALLY_REPAID, \echeanciers::STATUS_REPAID), 0);
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
        return $this->getRepaymentAmountInDateRange($lenderId, $startDate, $endDate, 'e.capital_rembourse + e.interets_rembourses', array(\echeanciers::STATUS_PARTIALLY_REPAID, \echeanciers::STATUS_REPAID), null, $loanId);
    }

    /**
     * @param int $lenderId
     * @param string $startDate
     * @param string $endDate
     * @return string
     */
    public function getNextRepaymentAmountInDateRange($lenderId, $startDate, $endDate)
    {
        return $this->getRepaymentAmountInDateRange($lenderId, $startDate, $endDate, 'e.capital + e.interets', array(\echeanciers::STATUS_PENDING));
    }

    /**
     * @param int $lenderId
     * @param int $loanId
     * @return string
     * @throws Exception
     */
    public function getTotalComingCapital($lenderId, $loanId)
    {
        $bind     = [
            'id_lender'        => $lenderId,
            'loan_status'      => \loans::STATUS_ACCEPTED,
            'id_loan'          => $loanId,
            'repayment_status' => \echeanciers::STATUS_PENDING,
            'date_echeance'    => date('Y-m-d')
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
        if (in_array(\echeanciers::STATUS_PENDING, $repaymentStatus)) {
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

        return bcdiv($this->bdd->executeQuery($query, $bind, $bindType, new \Doctrine\DBAL\Cache\QueryCacheProfile(\Unilend\librairies\CacheKeys::MEDIUM_TIME, md5(__METHOD__)))
            ->fetchColumn(0), 100, 2);
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
            WHERE id_project = :id_project GROUP BY ordre';

        $result = $this->bdd->executeQuery($sql, array('id_project' => $projectId), array('id_project' => \PDO::PARAM_INT), new \Doctrine\DBAL\Cache\QueryCacheProfile(\Unilend\librairies\CacheKeys::SHORT_TIME, md5(__METHOD__)))->fetchAll(\PDO::FETCH_ASSOC);
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
            SELECT IFNULL(ROUND(SUM(e.capital - e.capital_rembourse) / 100, 2), 0) AS capital, COUNT(DISTINCT(e.id_project)) AS projects
            FROM echeanciers e
            LEFT JOIN echeanciers unpaid ON unpaid.id_echeancier = e.id_echeancier AND unpaid.status = ' . self::STATUS_PENDING . ' AND DATEDIFF(NOW(), unpaid.date_echeance) > 180
            INNER JOIN loans l ON l.id_lender = e.id_lender AND l.id_loan = e.id_loan
            WHERE e.id_lender = :id_lender
                AND e.status IN(' . self::STATUS_PENDING . ', ' . self::STATUS_PARTIALLY_REPAID . ')
                AND l.status = 0
                AND (
                    (SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON ps.id_project_status = psh.id_project_status WHERE psh.id_project = e.id_project ORDER BY psh.id_project_status_history DESC LIMIT 1) >= ' . \projects_status::PROCEDURE_SAUVEGARDE . '
                    OR unpaid.date_echeance IS NOT NULL
                )';
        return $this->bdd->executeQuery($sql, array('id_lender' => $lenderId))->fetch(\PDO::FETCH_ASSOC);
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

    public function getDateDerniereEcheance($projectId)
    {
        $result = $this->bdd->query('SELECT MAX(date_echeance_emprunteur) FROM echeanciers WHERE id_project = ' . $projectId);
        return $this->bdd->result($result);
    }

    public function getDateDerniereEcheancePreteur($projectId)
    {
        $result = $this->bdd->query('SELECT MAX(date_echeance) FROM echeanciers WHERE id_project = ' . $projectId);
        return $this->bdd->result($result);
    }

    public function onMetAjourLesDatesEcheances($projectId, $ordre, $date_echeance, $date_echeance_emprunteur)
    {
        $sql = 'UPDATE echeanciers SET date_echeance = "' . $date_echeance . '", date_echeance_emprunteur = "' . $date_echeance_emprunteur . '", updated = "' . date('Y-m-d H:i:s') . '" WHERE status_emprunteur = 0 AND id_project = "' . $projectId . '" AND ordre = "' . $ordre . '" ';
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

    public function getRepaymentOfTheDay(\DateTime $oDate)
    {
        $sDate = $oDate->format('Y-m-d');

        $sQuery = '
           SELECT id_project,
              ordre,
              COUNT(*) AS nb_repayment,
              COUNT(CASE status WHEN ' . self::STATUS_REPAID . ' THEN 1 ELSE NULL END) AS nb_repayment_paid
            FROM echeanciers
            WHERE DATE(date_echeance) =  "' . $sDate . '"
            GROUP BY id_project, ordre';

        $rQuery  = $this->bdd->query($sQuery);
        $aResult = array();
        while ($aRow = $this->bdd->fetch_assoc($rQuery)) {
            $aResult[] = $aRow;
        }
        return $aResult;
    }

    // retourne la somme total a rembourser pour un projet
    public function get_liste_preteur_on_project($projectId = '')
    {
        $sql = 'SELECT * FROM `echeanciers`
                      WHERE id_project = ' . $projectId . '
                      GROUP BY id_loan';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function getLastOrder($iProjectID, $sDate = 'NOW()', $sInterval = 3)
    {
        $resultat = $this->bdd->query('
            SELECT *
            FROM `echeanciers`
            WHERE id_project = ' . $iProjectID . '
                AND DATE_ADD(date_echeance, INTERVAL ' . $sInterval . ' DAY) > ' . $sDate . '
                AND id_lender = (SELECT id_lender FROM echeanciers where id_project = ' . $iProjectID . ' LIMIT 1)
            GROUP BY id_project
            ORDER BY ordre ASC
            LIMIT 1'
        );
        $result   = $this->bdd->fetch_assoc($resultat);

        return $result;
    }

    /**
     * @param int $iLoanId
     * @param int $iAnticipatedRepaymentStatus
     * @param string $sOrder
     * @return array
     */
    public function getRepaymentWithTaxDetails($iLoanId, $iAnticipatedRepaymentStatus = 0, $sOrder = 'e.ordre ASC')
    {
        $sql     = '
        SELECT e.*, sum(ifnull(tax.amount, 0)) as tax
        FROM echeanciers e
            LEFT JOIN transactions t ON e.id_echeancier = t.id_echeancier AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
            LEFT JOIN tax ON t.id_transaction = tax.id_transaction
        WHERE e.id_loan = ' . $iLoanId . ' AND e.status_ra = ' . $iAnticipatedRepaymentStatus . '
        GROUP BY e.id_echeancier
        ORDER BY ' . $sOrder;
        $result  = $this->bdd->query($sql);
        $aReturn = array();
        while ($record = $this->bdd->fetch_array($result)) {
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
          IFNULL(
              (
                SELECT p.iso
                FROM lenders_imposition_history lih
                  JOIN pays_v2 p ON p.id_pays = lih.id_pays
                WHERE lih.added <= e.date_echeance_reel
                      AND lih.id_lender = e.id_lender
                ORDER BY lih.added DESC
                LIMIT 1
              ), "FR"
          )   AS iso_pays,
          CASE
              WHEN lte.year IS NULL THEN
                  0
              ELSE
                  1
          END AS exonere,
          (SELECT group_concat(lte.year SEPARATOR ", ")
           FROM lender_tax_exemption lte
           WHERE lte.id_lender = la.id_lender_account) AS annees_exoneration,
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
          INNER JOIN lenders_accounts la ON la.id_lender_account = e.id_lender
          INNER JOIN clients c ON c.id_client = la.id_client_owner
          LEFT JOIN lender_tax_exemption lte ON lte.id_lender = e.id_lender AND lte.year = YEAR(e.date_echeance_reel)
          INNER JOIN transactions t ON t.id_echeancier = e.id_echeancier AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
          LEFT JOIN tax prelevements_obligatoires ON prelevements_obligatoires.id_transaction = t.id_transaction AND prelevements_obligatoires.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX . '
          LEFT JOIN tax retenues_source ON retenues_source.id_transaction = t.id_transaction AND retenues_source.id_tax_type = ' . \tax_type::TYPE_INCOME_TAX_DEDUCTED_AT_SOURCE . '
          LEFT JOIN tax csg ON csg.id_transaction = t.id_transaction AND csg.id_tax_type = ' . \tax_type::TYPE_CSG . '
          LEFT JOIN tax prelevements_sociaux ON prelevements_sociaux.id_transaction = t.id_transaction AND prelevements_sociaux.id_tax_type = ' . \tax_type::TYPE_SOCIAL_DEDUCTIONS . '
          LEFT JOIN tax contributions_additionnelles ON contributions_additionnelles.id_transaction = t.id_transaction AND contributions_additionnelles.id_tax_type = ' . \tax_type::TYPE_ADDITIONAL_CONTRIBUTION_TO_SOCIAL_DEDUCTIONS . '
          LEFT JOIN tax prelevements_solidarite ON prelevements_solidarite.id_transaction = t.id_transaction AND prelevements_solidarite.id_tax_type = ' . \tax_type::TYPE_SOLIDARITY_DEDUCTIONS . '
          LEFT JOIN tax crds ON crds.id_transaction = t.id_transaction AND crds.id_tax_type = ' . \tax_type::TYPE_CRDS . '
        WHERE DATE(e.date_echeance_reel) = :date
              AND e.status IN (' . \echeanciers::STATUS_REPAID . ', ' . \echeanciers::STATUS_PARTIALLY_REPAID . ')
              AND e.status_ra = 0
        ORDER BY e.date_echeance ASC;
        ';

        return $this->bdd->executeQuery($sql, ['date' => $date->format('Y-m-d')], ['date' => \PDO::PARAM_STR])->fetchAll(\PDO::FETCH_ASSOC);
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
        $aBind = [
            'start_date'  => $startDate->format('Y-m-d'),
            'end_date'    => $endDate->format('Y-m-d'),
        ];
        $aType = [
            'start_date'  => \PDO::PARAM_STR,
            'end_date'    => \PDO::PARAM_STR,
        ];

        $sql = '
        SELECT
          l.id_type_contract,
          CASE c.type
          WHEN ' . \clients::TYPE_LEGAL_ENTITY . ' THEN
            "legal_entity"
          WHEN ' . \clients::TYPE_PERSON . ' OR ' . \clients::TYPE_PERSON_FOREIGNER . ' THEN
            "person"
          END AS client_type,
          CASE
            (SELECT
             IFNULL(resident_etranger, 0)
             FROM lenders_imposition_history lih
             WHERE lih.id_lender = la.id_lender_account AND lih.added <= e.date_echeance_reel
             ORDER BY added DESC LIMIT 1)
            WHEN 0 THEN "fr"
            ELSE "ww"
          END AS fiscal_residence,
          CASE lte.id_lender
            WHEN e.id_lender THEN
              "non_taxable"
            ELSE "taxable"
          END AS exemption_status,
          ' . $taxDynamicJoin['tax_columns'] . '
          SUM(ROUND(e.interets_rembourses / 100, 2)) AS interests
        FROM echeanciers e
          INNER JOIN loans l ON l.id_loan = e.id_loan AND l.status = 0
          INNER JOIN lenders_accounts la ON e.id_lender = la.id_lender_account
          INNER JOIN clients c ON la.id_client_owner = c.id_client
          INNER JOIN transactions t ON t.id_echeancier = e.id_echeancier AND t.type_transaction = 28
          ' . $taxDynamicJoin['tax_join'] . '
          LEFT JOIN lender_tax_exemption lte ON lte.id_lender = la.id_lender_account AND lte.year = YEAR(e.date_echeance_reel)
        WHERE e.status IN (' . \echeanciers::STATUS_REPAID . ', ' . \echeanciers::STATUS_PARTIALLY_REPAID . ')
              AND e.status_ra = 0
              AND DATE(e.date_echeance_reel) BETWEEN :start_date AND :end_date
        GROUP BY l.id_type_contract, client_type, fiscal_residence, exemption_status';

        return $this->bdd->executeQuery($sql, $aBind, $aType, new \Doctrine\DBAL\Cache\QueryCacheProfile(\Unilend\librairies\CacheKeys::LONG_TIME, md5(__METHOD__)))->fetchAll(\PDO::FETCH_ASSOC);
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
        return $this->getRepaymentsBetweenDate($taxType, $startDate, $endDate, [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER], 2, true);
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
                $taxExemptionJoin = ' LEFT JOIN lender_tax_exemption lte ON lte.id_lender = la.id_lender_account AND lte.year = YEAR(e.date_echeance_reel) ';
                $sExemptionWhere  = 'AND lte.id_lender IS NULL';
                break;
            case 1:
                $taxExemptionJoin = ' INNER JOIN lender_tax_exemption lte ON lte.id_lender = la.id_lender_account AND lte.year = YEAR(e.date_echeance_reel) ';
                break;
            default:
                $taxExemptionJoin = ' LEFT JOIN lender_tax_exemption lte ON lte.id_lender = la.id_lender_account ';
                break;
        }
        if (true === $foreigner) {
            $sForeignerWhere = '
            AND (SELECT resident_etranger FROM lenders_imposition_history lih WHERE lih.id_lender = la.id_lender_account AND lih.added <= e.date_echeance_reel ORDER BY added DESC LIMIT 1) > 0 ';
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
            INNER JOIN lenders_accounts la ON e.id_lender = la.id_lender_account
            INNER JOIN clients c ON la.id_client_owner = c.id_client
            INNER JOIN transactions t ON t.id_echeancier = e.id_echeancier AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS
            . $taxDynamicJoin['tax_join']
            . $taxExemptionJoin . '
        WHERE e.status IN (' . \echeanciers::STATUS_REPAID . ', ' . \echeanciers::STATUS_PARTIALLY_REPAID . ')
            AND e.status_ra = 0
            AND c.type IN (:client_type) '
            . $sExemptionWhere . $sForeignerWhere . '
            AND DATE(date_echeance_reel) BETWEEN :start_date AND :end_date
            GROUP BY l.id_type_contract';

        return $this->bdd->executeQuery($sql, $aBind, $aType, new \Doctrine\DBAL\Cache\QueryCacheProfile(\Unilend\librairies\CacheKeys::LONG_TIME, md5(__METHOD__)))->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param array $taxType array of id_tax_type to use in the query
     * @return array
     */
    private function getDynamicTaxJoins(array $taxType)
    {
        $taxColumns = $taxJoin = '';

        foreach ($taxType as $row){
            $taxName = 'tax_' . $row['id_tax_type'];
            $taxColumns .= ' ROUND(SUM(' . $taxName . '.amount) / 100, 2) AS ' . $taxName . ', ';
            $taxJoin    .= ' LEFT JOIN tax ' . $taxName . ' ON ' . $taxName . '.id_transaction = t.id_transaction AND ' . $taxName . '.id_tax_type = ' . $row['id_tax_type'];
        }
        return ['tax_join' => $taxJoin, 'tax_columns' => $taxColumns];
    }
}
