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

class echeanciers_emprunteur extends echeanciers_emprunteur_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::echeanciers_emprunteur($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `echeanciers_emprunteur`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `echeanciers_emprunteur` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_echeancier_emprunteur')
    {
        $sql    = 'SELECT * FROM `echeanciers_emprunteur` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function sum($sum, $where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT sum(' . $sum . ') as sum FROM `echeanciers_emprunteur` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function onMetAjourTVA($taux)
    {
        $sql = 'UPDATE echeanciers_emprunteur SET tva = ROUND(commission * ' . $taux . ') WHERE status_emprunteur = 0';
        $this->bdd->query($sql);
    }

    public function onMetAjourLesDatesEcheancesE($id_project, $ordre, $date_echeance_emprunteur)
    {
        $sql = 'UPDATE echeanciers_emprunteur SET date_echeance_emprunteur = "' . $date_echeance_emprunteur . '", updated = "' . date('Y-m-d H:i:s') . '" WHERE status_emprunteur = 0 AND id_project = "' . $id_project . '" AND ordre = "' . $ordre . '" ';
        $this->bdd->query($sql);
    }

    // retourne le montant restant à payer pour le projet
    public function get_restant_du($id_project, $date_debut)
    {
        $sql = '
            SELECT SUM(montant) AS montant
            FROM echeanciers_emprunteur
            WHERE id_project = ' . $id_project . '
                AND status_emprunteur = 0
                AND DATE(date_echeance_emprunteur) > "' . $date_debut . '"';
        $result  = $this->bdd->query($sql);
        return $this->bdd->result($result, 0, 0);
    }

    // retourne le montant restant à payer pour le projet
    public function get_capital_restant_du($id_project, $date_debut)
    {
        $sql = '
            SELECT SUM(capital) AS montant
            FROM echeanciers_emprunteur
            WHERE id_project = ' . $id_project . '
                AND status_emprunteur = 0
                AND DATE(date_echeance_emprunteur) > "' . $date_debut . '"';
        $result  = $this->bdd->query($sql);
        return $this->bdd->result($result, 0, 0);
    }

    // retourne la somme total a rembourser pour un porjet
    public function reste_a_payer_ra($id_project = '', $ordre = '')
    {
        $sql = 'SELECT SUM(capital) FROM `echeanciers_emprunteur`
                        WHERE status_emprunteur = 0
                        AND ordre >= "' . $ordre . '"
                        AND id_project = ' . $id_project;

        $result = $this->bdd->query($sql);
        $sum    = (int) ($this->bdd->result($result, 0, 0));
        return ($sum / 100);
    }

    /**
     * @param int $iDaysInterval
     * @return array
     */
    public function getUpcomingRepayments($iDaysInterval)
    {
        $sNextWeekPayment = '
            SELECT ee.*
            FROM echeanciers_emprunteur ee
            INNER JOIN projects_last_status_history plsh ON plsh.id_project = ee.id_project
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            WHERE ps.status = '. \projects_status::REMBOURSEMENT .' AND status_emprunteur = 0 AND DATE_ADD(CURDATE(), INTERVAL '. $iDaysInterval .' DAY) = DATE(date_echeance_emprunteur)';

        $rResult          = $this->bdd->query($sNextWeekPayment);
        $aNextWeekPayment = array();
        while ($aRecord = $this->bdd->fetch_assoc($rResult)) {
            $aNextWeekPayment[] = $aRecord;
        }
        return $aNextWeekPayment;
    }

    /**
     * @param string $scheduleDate
     * @return int
     * @throws Exception
     */
    public function getCostsAndVatAmount($scheduleDate)
    {
        $sql = '
            SELECT 
              IFNULL(SUM(ee.tva + ee.commission), 0)
            FROM echeanciers_emprunteur ee
            WHERE ee.id_echeancier_emprunteur IN (
              SELECT GROUP_CONCAT(bu.id_echeance_emprunteur) FROM bank_unilend bu WHERE DATE(bu.added) = :schedule_date AND bu.type = 2  AND bu.status = 1 GROUP BY DATE(bu.added)
            )
        ';
        return $this->bdd->executeQuery($sql,
            ['schedule_date' => $scheduleDate],
            ['schedule_date' => \PDO::PARAM_STR])->fetchColumn(0);
    }
}
