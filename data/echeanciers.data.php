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
        $sql = 'SELECT * FROM echeanciers' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));
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
     * @param array $comparison
     * @return float
     */
    public function getTotalAmount(array $selector, array $comparison)
    {
        return $this->getPartialSum('capital + interets', $selector, $comparison);
    }

    /**
     * @param array $selector
     * @param array $comparison
     * @return float
     */
    public function getTotalInterests(array $selector, array $comparison)
    {
        return $this->getPartialSum('interets', $selector, $comparison);
    }

    /**
     * @param array $selector
     * @param array $comparison
     * @return float
     */
    public function getTotalCapital(array $selector, array $comparison)
    {
        return $this->getPartialSum('capital', $selector, $comparison);
    }

    /**
     * @param array $selector
     * @param array $comparison
     * @return float
     */
    public function getOwedAmount(array $selector, array $comparison)
    {
        return $this->getPartialSum('capital - capital_rembourse + interets - interets_rembourses', $selector, $comparison, array(self::STATUS_PENDING, self::STATUS_PARTIALLY_REPAID));
    }

    /**
     * @param array $selector
     * @param array $comparison
     * @return float
     */
    public function getOwedCapital(array $selector, array $comparison)
    {
        return $this->getPartialSum('capital - capital_rembourse', $selector, $comparison, array(self::STATUS_PENDING, self::STATUS_PARTIALLY_REPAID));
    }

    /**
     * @param array $selector
     * @param array $comparison
     * @return string
     */
    public function getRepaidAmount(array $selector, array $comparison)
    {
        return bcadd($this->getRepaidCapital($selector, $comparison), $this->getRepaidInterests($selector, $comparison));
    }

    /**
     * @param array $selector
     * @param array $comparison
     * @return float
     */
    public function getRepaidCapital(array $selector, array $comparison)
    {
        return $this->getPartialSum('capital_rembourse', $selector, $comparison, array(self::STATUS_REPAID, self::STATUS_PARTIALLY_REPAID));
    }

    /**
     * @param array $selector
     * @param $comparison
     * @return float
     */
    public function getEarlyRepaidCapital(array $selector, $comparison)
    {
        return $this->getPartialSum('capital_rembourse', $selector, $comparison, array(self::STATUS_REPAID, self::STATUS_PARTIALLY_REPAID), 1);
    }

    /**
     * @param array $selector
     * @param array $comparison
     * @return float
     */
    public function getRepaidInterests(array $selector, array $comparison)
    {
        return $this->getPartialSum('interets_rembourses', $selector, $comparison, array(self::STATUS_REPAID, self::STATUS_PARTIALLY_REPAID), 0);
    }

    /**
     * @param string $amountType
     * @param array $selector
     * @param array $comparison
     * @param array $status
     * @param int|null $earlyRepaymentStatus
     * @return float
     */
    private function getPartialSum($amountType, array $selector, array $comparison, array $status = array(), $earlyRepaymentStatus = null)
    {
        $query = '
            SELECT SUM(e.' . $amountType . ')
            FROM echeanciers e
            INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE l.status = ' . \loans::ACCEPTED_STATUS . ' AND e.' . $this->implodeSelector($selector, $comparison);

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
            WHERE YEAR(date_echeance_reel) = ' . $year . ' AND ' . $this->implodeSelector($selector) . '
            GROUP BY mois'
        );

        while ($record = $this->bdd->fetch_assoc($queryResult)) {
            $result[$record['mois']] = $record;
        }
        return $result;
    }

    /**
     * @param array $selector
     * @param array $comparison
     * @return string
     */
    private function implodeSelector(array $selector, array $comparison)
    {
        return implode(' AND e.', array_map(
            function ($key, $value, $comparison) {
                return $key . $comparison . $value;
            },
            array_keys($selector),
            $selector,
            $comparison
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
     * @param int $iLenderId
     * @param int $iStartYear
     * @param int $iEndYear
     * @return array
     */
    public function getRepaidCapitalInDateRange($iLenderId, $iStartYear, $iEndYear)
    {
        return $this->getRepaidAmountInDateRange($iLenderId, $iStartYear, $iEndYear, 'capital');
    }

    /**
     * @param int $iLenderId
     * @param int $iStartYear
     * @param int $iEndYear
     * @return array
     */
    public function getRepaidInterestsInDateRange($iLenderId, $iStartYear, $iEndYear)
    {
        return $this->getRepaidAmountInDateRange($iLenderId, $iStartYear, $iEndYear, 'interests');
    }

    /**
     * @param int $iLenderId
     * @param int $iStartYear
     * @param int $iEndYear
     * @param string $sAmountType
     * @return array
     */
    public function getRepaidAmountInDateRange($iLenderId, $iStartYear, $iEndYear, $sAmountType = 'amount')
    {
        switch ($sAmountType) {
            case 'capital':
                $method = 'getRepaidCapital';
                break;
            case 'interests':
                $method = 'getRepaidInterests';
                break;
            default:
                $method = 'getRepaidAmount';
                break;
        }
        $aResult = [];
        for ($iYear = $iStartYear; $iYear <= $iEndYear; $iYear++) {
            $aResult[$iYear] = number_format($this->$method(array(
                    'id_lender' => $iLenderId,
                    'date_echeance_reel' => '"' . $iYear . '-01-01 00:00:00" AND "' . $iYear . '-12-31 23:59:59"'
                    ),
                array(' = ', ' BETWEEN ')
            ), 2, '.', '');
        }
        return $aResult;
    }

    /**
     * @param int $iProjectId
     * @return array
     */
    public function getMonthlyScheduleByProject($iProjectId)
    {
        $sql = '
            SELECT ordre,
                SUM(montant) AS montant,
                SUM(capital) AS capital,
                SUM(interets) AS interets,
                status_emprunteur
            FROM echeanciers
            WHERE id_project = :id_project GROUP BY ordre';

        $result = $this->bdd->executeQuery($sql, array('id_project' => $iProjectId), array('id_project' => \PDO::PARAM_INT), new \Doctrine\DBAL\Cache\QueryCacheProfile(\Unilend\librairies\CacheKeys::SHORT_TIME, md5(__METHOD__)))->fetchAll(\PDO::FETCH_ASSOC) ;
        foreach ($result as $key => $aRow) {
            $res[$aRow['ordre']] = array(
                'montant'           => $aRow['montant'] / 100,
                'capital'           => $aRow['capital'] / 100,
                'interets'          => $aRow['interets'] / 100,
                'status_emprunteur' => $aRow['status_emprunteur']
            );
        }
        return $res;
    }

    /**
     * @param int $iLenderId
     * @return array
     */
    public function getProblematicProjects($iLenderId)
    {
        $sql = '
            SELECT ifnull(ROUND(SUM(e.capital - e.capital_rembourse) / 100, 2), 0) AS capital, COUNT(DISTINCT(e.id_project)) AS projects
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
        return $this->bdd->executeQuery($sql, array('id_lender' => $iLenderId))->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param int $id_project
     * @param int $ordre
     * @param string $annuler
     */
    public function updateStatusEmprunteur($id_project, $ordre, $annuler = '')
    {
        if ($annuler != '') {
            $sql = 'UPDATE echeanciers SET status_emprunteur = 0, date_echeance_emprunteur_reel = "0000-00-00 00:00:00", updated = "' . date('Y-m-d H:i:s') . '" WHERE id_project = ' . $id_project . ' AND ordre = ' . $ordre;
        } else {
            $sql = 'UPDATE echeanciers SET status_emprunteur = 1, date_echeance_emprunteur_reel = "' . date('Y-m-d H:i:s') . '", updated = "' . date('Y-m-d H:i:s') . '" WHERE id_project = ' . $id_project . ' AND ordre = ' . $ordre;
        }

        $this->bdd->query($sql);
    }

    // premiere echance emprunteur
    public function getPremiereEcheancePreteur($id_project, $id_lender)
    {
        // premiere echeance
        $PremiereEcheance = $this->select('ordre = 1 AND id_project = ' . $id_project . ' AND id_lender = ' . $id_lender, '', 0, 1);
        return $PremiereEcheance[0];
    }

    // on recup la premiere echeance d'un pret d'un preteur
    public function getPremiereEcheancePreteurByLoans($id_project, $id_lender, $id_loan)
    {
        // premiere echeance
        $PremiereEcheance = $this->select('ordre = 1 AND id_project = ' . $id_project . ' AND id_lender = ' . $id_lender . ' AND id_loan = ' . $id_loan, '', 0, 1);
        return $PremiereEcheance[0];
    }

    // premiere echance emprunteur
    public function getDatePremiereEcheance($id_project)
    {
        // premiere echeance
        $PremiereEcheance = $this->select('ordre = 1 AND id_project = ' . $id_project, '', 0, 1);
        return $PremiereEcheance[0]['date_echeance_emprunteur'];
    }

    public function getDateDerniereEcheance($id_project)
    {
        $result = $this->bdd->query('SELECT MAX(date_echeance_emprunteur) FROM echeanciers WHERE id_project = ' . $id_project);
        return $this->bdd->result($result);
    }

    public function getDateDerniereEcheancePreteur($id_project)
    {
        $result = $this->bdd->query('SELECT MAX(date_echeance) FROM echeanciers WHERE id_project = ' . $id_project);
        return $this->bdd->result($result);
    }

    /**
     * @deprecated
     */
    public function getEcheanceBetweenDates_exonere_mais_pas_dans_les_dates($date1, $date2)
    {
        $anneemois = explode('-', $date1);
        $anneemois = $anneemois[0] . '-' . $anneemois[1];

        $sql = '
            SELECT
                l.id_type_contract,
                SUM(montant) AS montant,
                SUM(capital) AS capital,
                SUM(interets) AS interets,
                SUM(prelevements_obligatoires) AS prelevements_obligatoires,
                SUM(retenues_source) AS retenues_source,
                SUM(csg) AS csg,
                SUM(prelevements_sociaux) AS prelevements_sociaux,
                SUM(contributions_additionnelles) AS contributions_additionnelles,
                SUM(prelevements_solidarite) AS prelevements_solidarite,
                SUM(crds) AS crds
            FROM echeanciers e
            LEFT JOIN loans l ON l.id_loan = e.id_loan
            LEFT JOIN lenders_accounts la ON e.id_lender = la.id_lender_account
            LEFT JOIN clients c ON la.id_client_owner = c.id_client
            WHERE e.status = ' . self::STATUS_REPAID . '
                AND e.status_ra = 0
                AND c.type IN (' . \clients::TYPE_PERSON . ', ' . \clients::TYPE_PERSON_FOREIGNER . ')
                AND la.exonere = 1
                AND "' . $anneemois . '" NOT BETWEEN LEFT(la.debut_exoneration, 7) AND LEFT(la.fin_exoneration, 7)
                AND DATE(date_echeance_reel) BETWEEN "' . $date1 . '" AND "' . $date2 . '"
            GROUP BY l.id_type_contract';

        $aReturn  = array();
        $aResults = $this->bdd->query($sql);

        while ($aResult = $this->bdd->fetch_assoc($aResults)) {
            $aReturn[$aResult['id_type_contract']] = $aResult;
        }

        return $aReturn;
    }

    /**
     * @deprecated
     */
    public function getEcheanceBetweenDatesEtranger($date1, $date2)
    {
        $sql = '
            SELECT
                l.id_type_contract,
                SUM(montant) AS montant,
                SUM(capital) AS capital,
                SUM(interets) AS interets,
                SUM(prelevements_obligatoires) AS prelevements_obligatoires,
                SUM(retenues_source) AS retenues_source,
                SUM(csg) AS csg,
                SUM(prelevements_sociaux) AS prelevements_sociaux,
                SUM(contributions_additionnelles) AS contributions_additionnelles,
                SUM(prelevements_solidarite) AS prelevements_solidarite,
                SUM(crds) AS crds
            FROM echeanciers e
            LEFT JOIN loans l ON l.id_loan = e.id_loan
            LEFT JOIN lenders_accounts la ON e.id_lender = la.id_lender_account
            LEFT JOIN clients c ON la.id_client_owner = c.id_client
            WHERE e.status = ' . self::STATUS_REPAID . '
                AND e.status_ra = 0
                AND c.type IN (' . \clients::TYPE_PERSON . ', ' . \clients::TYPE_PERSON_FOREIGNER . ')
                AND (SELECT resident_etranger FROM lenders_imposition_history lih WHERE lih.id_lender = la.id_lender_account AND lih.added <= e.date_echeance_reel ORDER BY added DESC LIMIT 1) > 0
                AND DATE(date_echeance_reel) BETWEEN "' . $date1 . '" AND "' . $date2 . '"
            GROUP BY l.id_type_contract';

        $aReturn  = array();
        $aResults = $this->bdd->query($sql);

        while ($aResult = $this->bdd->fetch_assoc($aResults)) {
            $aReturn[$aResult['id_type_contract']] = $aResult;
        }

        return $aReturn;
    }

    /**
     * @deprecated
     */
    public function getEcheanceBetweenDates($date1, $date2, $exonere = '', $morale = '')
    {
        $anneemois = explode('-', $date1);
        $anneemois = $anneemois[0] . '-' . $anneemois[1];

        if (is_array($morale)) {
            $morale = implode(',', $morale);
        }

        $sql = '
            SELECT
                l.id_type_contract,
                SUM(montant) AS montant,
                SUM(capital) AS capital,
                SUM(interets) AS interets,
                SUM(prelevements_obligatoires) AS prelevements_obligatoires,
                SUM(retenues_source) AS retenues_source,
                SUM(csg) AS csg,
                SUM(prelevements_sociaux) AS prelevements_sociaux,
                SUM(contributions_additionnelles) AS contributions_additionnelles,
                SUM(prelevements_solidarite) AS prelevements_solidarite,
                SUM(crds) AS crds
            FROM echeanciers e
            LEFT JOIN loans l ON l.id_loan = e.id_loan
            LEFT JOIN lenders_accounts la ON e.id_lender = la.id_lender_account
            LEFT JOIN clients c ON la.id_client_owner = c.id_client
            WHERE e.status = ' . self::STATUS_REPAID . '
                AND e.status_ra = 0
                ' . ($morale != '' ? ' AND c.type IN (' . $morale . ')' : '');

        if ($exonere != '') {
            if ($exonere == '1') {
                $sql .= '
                     AND la.exonere = 1
                     AND "' . $anneemois . '" BETWEEN LEFT(la.debut_exoneration, 7) AND LEFT(la.fin_exoneration, 7)';
            } else {
                $sql .= ' AND la.exonere = ' . $exonere;
            }
        }

        $sql .= '
                AND DATE(date_echeance_reel) BETWEEN "' . $date1 . '" AND "' . $date2 . '"
            GROUP BY l.id_type_contract';

        $aReturn  = array();
        $aResults = $this->bdd->query($sql);

        while ($aResult = $this->bdd->fetch_assoc($aResults)) {
            $aReturn[$aResult['id_type_contract']] = $aResult;
        }

        return $aReturn;
    }

    public function onMetAjourLesDatesEcheances($id_project, $ordre, $date_echeance, $date_echeance_emprunteur)
    {
        $sql = 'UPDATE echeanciers SET date_echeance = "' . $date_echeance . '", date_echeance_emprunteur = "' . $date_echeance_emprunteur . '", updated = "' . date('Y-m-d H:i:s') . '" WHERE status_emprunteur = 0 AND id_project = "' . $id_project . '" AND ordre = "' . $ordre . '" ';
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

        $rQuery = $this->bdd->query($sQuery);
        $aResult   = array();
        while ($aRow = $this->bdd->fetch_assoc($rQuery)) {
            $aResult[] = $aRow;
        }
        return $aResult;
    }

    // retourne la somme total a rembourser pour un projet
    public function get_liste_preteur_on_project($id_project = '')
    {
        $sql = 'SELECT * FROM `echeanciers`
                      WHERE id_project = ' . $id_project . '
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
        $result = $this->bdd->fetch_assoc($resultat);

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
        $sql = '
        SELECT e.*, sum(ifnull(tax.amount, 0)) as tax
        FROM echeanciers e
            LEFT JOIN transactions t ON e.id_echeancier = t.id_echeancier AND t.type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS . '
            LEFT JOIN tax ON t.id_transaction = tax.id_transaction
        WHERE e.id_loan = ' . $iLoanId . ' AND e.status_ra = ' . $iAnticipatedRepaymentStatus . '
        GROUP BY e.id_echeancier
        ORDER BY ' . $sOrder ;
        $result = $this->bdd->query($sql);
        $aReturn = array();
        while ($record = $this->bdd->fetch_array($result)) {
            $aReturn[] = $record;
        }
        return $aReturn;
    }
}
