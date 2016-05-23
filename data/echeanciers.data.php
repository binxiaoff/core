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
    public function getRepaidAmount(array $selector)
    {
        return bcadd($this->getRepaidCapital($selector), $this->getRepaidInterests($selector));
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
            WHERE l.status = 0 AND e.' . $this->implodeSelector($selector);

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
     * @deprecated
     * @param string $where
     * @param string $champ
     * @return string
     */
    public function sum($where, $champ = 'montant')
    {
        $result = $this->bdd->query('SELECT SUM(' . $champ . ') FROM echeanciers WHERE ' . $where);
        return bcdiv($this->bdd->result($result), 100, 2);
    }

    /**
     * @deprecated
     * Retourne la somme des echeances a rembourser d'un preteur sur les prêts acceptés
     */
    public function getSumARemb($id_lender, $champ = 'montant')
    {
        $result = $this->bdd->query("
            SELECT SUM(e.$champ)
            FROM echeanciers e
            INNER JOIN loans l ON l.id_lender = e.id_lender AND l.id_loan = e.id_loan
            WHERE e.id_lender = $id_lender
                AND l.status = 0"
        );
        return (int) ($this->bdd->result($result) / 100);
    }

    /**
     * @deprecated
     * Retourne la somme des revenues fiscale des echeances deja remboursés d'un preteur
     */
    public function getSumRevenuesFiscalesRemb($id_lender)
    {
        $sql = 'SELECT SUM(prelevements_obligatoires) as prelevements_obligatoires,SUM(retenues_source) as retenues_source,SUM(csg) as csg,SUM(prelevements_sociaux) as prelevements_sociaux,SUM(contributions_additionnelles) as contributions_additionnelles,SUM(prelevements_solidarite) as prelevements_solidarite,SUM(crds) as crds FROM `echeanciers` WHERE status = ' . self::STATUS_REPAID . ' AND id_lender = ' . $id_lender;

        $retenues = 0;
        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_array($resultat)) {
            $retenues += $record['prelevements_obligatoires'] + $record['retenues_source'] + $record['csg'] + $record['prelevements_sociaux'] + $record['contributions_additionnelles'] + $record['prelevements_solidarite'] + $record['crds'];
        }
        return $retenues;
    }

    /**
     * @deprecated
     * Nb period restantes
     */
    public function counterPeriodRestantes($id_lender, $id_project)
    {
        $sql = 'SELECT count(DISTINCT(ordre)) FROM `echeanciers` WHERE id_lender = ' . $id_lender . ' AND id_project = ' . $id_project . ' AND status = ' . self::STATUS_PENDING;

        $result = $this->bdd->query($sql);
        return (int) $this->bdd->result($result);
    }

    /**
     * @deprecated
     * Retourne la sommes des remb du prochain mois d'un preteur
     */
    public function getNextRemb($id_lender)
    {
        $laDate = mktime(0, 0, 0, date("m") + 1, date("d"), date("Y"));
        $laDate = date('Y-m', $laDate);

        $sql = 'SELECT SUM(montant) FROM `echeanciers` WHERE status = ' . self::STATUS_PENDING . ' AND id_lender = ' . $id_lender . ' AND LEFT(date_echeance, 7) = "' . $laDate . '"';

        $result = $this->bdd->query($sql);
        $sum    = $this->bdd->result($result);
        if ($sum == 0) {
            $sum = 0;
        } else {
            $sum = $sum / 100;
        }
        return $sum;
    }

    /**
     * @deprecated
     */
    public function getSumRevenuesFiscalesByMonths($id_lender, $year)
    {
        $sql = 'SELECT
        SUM(prelevements_obligatoires) as prelevements_obligatoires,
        SUM(retenues_source) as retenues_source,
        SUM(csg) as csg,
        SUM(prelevements_sociaux) as prelevements_sociaux,
        SUM(contributions_additionnelles) as contributions_additionnelles,
        SUM(prelevements_solidarite) as prelevements_solidarite,
        SUM(crds) as crds,
        LEFT(date_echeance_reel, 7) AS date
        FROM `echeanciers`
        WHERE status = ' . self::STATUS_REPAID . '
        AND id_lender = ' . $id_lender . '
        AND YEAR(date_echeance_reel) = ' . $year . '
        GROUP BY LEFT(date_echeance_reel, 7)';

        $res    = array();
        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_array($result)) {
            $d          = explode('-', $record['date']);
            $retenues   = $record['prelevements_obligatoires'] + $record['retenues_source'] + $record['csg'] + $record['prelevements_sociaux'] + $record['contributions_additionnelles'] + $record['prelevements_solidarite'] + $record['crds'];
            $res[$d[1]] = $retenues;
        }
        return $res;
    }

    /**
     * @deprecated
     * Retourne les sommes remboursées chaque annee d'un preteur(capital)
     */
    public function getSumRembByYearCapital($id_lender, $debut, $fin)
    {
        $req = $this->bdd->query('
            SELECT SUM(capital_rembourse) AS capital,
                YEAR(date_echeance_reel) AS date
            FROM echeanciers
            WHERE YEAR(date_echeance_reel) >= ' . $debut . '
                AND YEAR(date_echeance_reel) <= ' . $fin . '
                AND id_lender = ' . $id_lender . '
                AND status IN(' . self::STATUS_REPAID . ', ' . self::STATUS_PARTIALLY_REPAID . ')
            GROUP BY YEAR(date_echeance_reel)'
        );

        $res      = array();
        $resultat = array();
        while ($rec = $this->bdd->fetch_array($req)) {
            $res[$rec['date']] = ($rec['capital'] > 0 ? ($rec['capital'] / 100) : 0);
        }

        for ($i = $debut; $i <= $fin; $i++) {
            $resultat[$i] = number_format(isset($res[$i]) ? $res[$i] : 0, 2, '.', '');
        }

        return $resultat;
    }

    /**
     * @deprecated
     * Retourne la somme des interets par annee d'un preteur
     */
    public function getSumIntByYear($id_lender, $debut, $fin)
    {
        $req = $this->bdd->query('
            SELECT SUM(interets_rembourses) AS interets,
                YEAR(date_echeance_reel) AS date
            FROM echeanciers
            WHERE YEAR(date_echeance_reel) >= ' . $debut . '
                AND YEAR(date_echeance_reel) <= ' . $fin . '
                AND id_lender = ' . $id_lender . '
                AND status IN(' . self::STATUS_REPAID . ', ' . self::STATUS_PARTIALLY_REPAID . ')
            GROUP BY YEAR(date_echeance_reel)'
        );

        $res      = array();
        $resultat = array();
        while ($rec = $this->bdd->fetch_array($req)) {
            $res[$rec['date']] = ($rec['interets'] > 0 ? ($rec['interets'] / 100) : 0);
        }

        for ($i = $debut; $i <= $fin; $i++) {
            $resultat[$i] = number_format(isset($res[$i]) ? $res[$i] : 0, 2, '.', '');
        }

        return $resultat;
    }

    /**
     * @deprecated
     * Prélèvements fiscaux chaque année
     */
    public function getSumRevenuesFiscalesByYear($id_lender, $debut, $fin)
    {
        $result = $this->bdd->query('
            SELECT
                SUM(prelevements_obligatoires) as prelevements_obligatoires,
                SUM(retenues_source) as retenues_source,
                SUM(csg) as csg,
                SUM(prelevements_sociaux) as prelevements_sociaux,
                SUM(contributions_additionnelles) as contributions_additionnelles,
                SUM(prelevements_solidarite) as prelevements_solidarite,
                SUM(crds) as crds,
                YEAR(date_echeance_reel) AS date
            FROM echeanciers
            WHERE status IN(' . self::STATUS_REPAID . ', ' . self::STATUS_PARTIALLY_REPAID . ')
                AND id_lender = ' . $id_lender . '
                AND YEAR(date_echeance_reel) >= ' . $debut . '
                AND YEAR(date_echeance_reel) <= ' . $fin . '
            GROUP BY YEAR(date_echeance_reel)'
        );

        $res      = array();
        $resultat = array();
        while ($record = $this->bdd->fetch_array($result)) {
            $retenues             = $record['prelevements_obligatoires'] + $record['retenues_source'] + $record['csg'] + $record['prelevements_sociaux'] + $record['contributions_additionnelles'] + $record['prelevements_solidarite'] + $record['crds'];
            $res[$record['date']] = $retenues;
        }

        for ($i = $debut; $i <= $fin; $i++) {
            $resultat[$i] = number_format(isset($res[$i]) ? $res[$i] : 0, 2, '.', '');
        }

        return $resultat;
    }

    /**
     * @deprecated
     * Retourne un tableau avec les sommes des echeances par mois d'un projet
     */
    public function getSumRembEmpruntByMonths($id_project)
    {
        $sql = '
            SELECT ordre,
                SUM(montant) AS montant,
                SUM(capital) AS capital,
                SUM(interets) AS interets,
                status_emprunteur
            FROM echeanciers
            WHERE id_project = ' . $id_project . '
            GROUP BY ordre';
        $res = array();
        $req = $this->bdd->query($sql);
        while ($rec = $this->bdd->fetch_array($req)) {
            $res[$rec['ordre']] = array(
                'montant'           => $rec['montant'] / 100,
                'capital'           => $rec['capital'] / 100,
                'interets'          => $rec['interets'] / 100,
                'status_emprunteur' => $rec['status_emprunteur']
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
        $rResult = $this->bdd->query('
            SELECT ROUND(SUM(e.capital - e.capital_rembourse) / 100, 2) AS capital, COUNT(DISTINCT(e.id_project)) AS projects
            FROM echeanciers e
            LEFT JOIN echeanciers unpaid ON unpaid.id_echeancier = e.id_echeancier AND unpaid.status = ' . self::STATUS_PENDING . ' AND DATEDIFF(NOW(), unpaid.date_echeance) > 180
            INNER JOIN loans l ON l.id_lender = e.id_lender AND l.id_loan = e.id_loan
            WHERE e.id_lender = ' . $iLenderId . '
                AND e.status = IN(' . self::STATUS_PENDING . ', ' . self::STATUS_PARTIALLY_REPAID . ')
                AND l.status = 0
                AND (
                    (SELECT ps.status FROM projects_status ps LEFT JOIN projects_status_history psh ON ps.id_project_status = psh.id_project_status WHERE psh.id_project = e.id_project ORDER BY psh.id_project_status_history DESC LIMIT 1) >= ' . \projects_status::PROCEDURE_SAUVEGARDE . '
                    OR unpaid.date_echeance IS NOT NULL
                )'
        );
        return $this->bdd->fetch_assoc($rResult);
    }

    // mise a jour des statuts emprunteur pour les remb d'un projet
    // id_project : projet
    // $ordre : periode de remb
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
        // premiere echeance
        $derniereEcheance = $this->select('id_project = ' . $id_project, 'ordre DESC', 0, 1);
        return $derniereEcheance[0]['date_echeance_emprunteur'];
    }

    public function getDateDerniereEcheancePreteur($id_project)
    {
        // premiere echeance
        $derniereEcheance = $this->select('id_project = ' . $id_project, 'ordre DESC', 0, 1);
        return $derniereEcheance[0]['date_echeance'];
    }

    /**
     * @deprecated
     */
    public function getEcheanceByDayAll($date, $statut = self::STATUS_PENDING)
    {
        $sql = 'SELECT
        SUM(montant) as montant,
        SUM(capital) as capital,
        SUM(interets) as interets,
        SUM(prelevements_obligatoires) as prelevements_obligatoires,
        SUM(retenues_source) as retenues_source,
        SUM(csg) as csg,
        SUM(prelevements_sociaux) as prelevements_sociaux,
        SUM(contributions_additionnelles) as contributions_additionnelles,
        SUM(prelevements_solidarite) as prelevements_solidarite,
        SUM(crds) as crds
        FROM `echeanciers` WHERE status = ' . $statut . ' AND DATE(date_echeance_reel) = "' . $date . '" GROUP BY DATE(date_echeance_reel)';


        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result[0];
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

    /**
     * @todo Replace calls before taxation is finished
     * @deprecated
     */
    public function selectEcheances_a_remb($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = '
            SELECT
                id_echeancier,
                id_lender,
                id_project,
                montant,
                capital,
                interets,
                ROUND((capital / 100), 2) AS capital_net,
                ROUND((ROUND((interets / 100), 2) - prelevements_obligatoires - retenues_source - csg - prelevements_sociaux - contributions_additionnelles - prelevements_solidarite - crds), 2) AS interets_net,
                ROUND((ROUND((montant / 100), 2) - prelevements_obligatoires - retenues_source - csg - prelevements_sociaux - contributions_additionnelles - prelevements_solidarite - crds), 2) AS rembNet,
                ROUND((prelevements_obligatoires + retenues_source + csg + prelevements_sociaux + contributions_additionnelles + prelevements_solidarite + crds), 2) AS etat,
                status
            FROM echeanciers' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
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

    /**
     * @deprecated
     * Retourne la somme total a rembourser pour un projet
     */
    public function reste_a_payer_ra($id_project = '', $ordre = '')
    {
        $result = $this->bdd->query('
            SELECT SUM(capital - capital_rembourse)
            FROM echeanciers
            WHERE status IN(' . self::STATUS_PENDING . ', ' . self::STATUS_PARTIALLY_REPAID . ')
                AND ordre >= "' . $ordre . '"
                AND id_project = ' . $id_project
        );
        $sum    = (int) $this->bdd->result($result);
        return ($sum / 100);
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
}
