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
class prelevements extends prelevements_crud
{
    const STATUS_PENDING             = 0;
    const STATUS_SENT                = 1;
    const STATUS_VALID               = 2;
    const STATUS_TERMINATED          = 3;
    const STATUS_TEMPORARILY_BLOCKED = 4;

    const CLIENT_TYPE_LENDER   = 1;
    const CLIENT_TYPE_BORROWER = 2;

    public function __construct($bdd, $params = '')
    {
        parent::prelevements($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `prelevements`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $result = $this->bdd->query('SELECT COUNT(*) FROM `prelevements` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_prelevement')
    {
        $result = $this->bdd->query('SELECT * FROM `prelevements` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function sum($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $result = $this->bdd->query('SELECT SUM(montant) FROM `prelevements` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    /**
     * @param int $daysInterval
     * @return array
     */
    public function getUpcomingRepayments($daysInterval)
    {
        $sql = '
            SELECT p.id_project, p.num_prelevement, p.date_echeance_emprunteur, p.montant
            FROM prelevements p
            INNER JOIN projects_last_status_history plsh ON plsh.id_project = p.id_project
            INNER JOIN echeanciers_emprunteur ee ON ee.ordre = p.num_prelevement
            INNER JOIN projects_status_history psh ON psh.id_project_status_history = plsh.id_project_status_history
            INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
            WHERE ps.status = :projectStatus
              AND ee.status_emprunteur = 0
              AND p.id_project = ee.id_project
              AND p.type = :directDebitStatus
              AND DATE_ADD(CURDATE(), INTERVAL :daysInterval DAY) = DATE(p.date_echeance_emprunteur)';

        $paramValues = array('daysInterval' => $daysInterval, 'projectStatus' => \projects_status::REMBOURSEMENT, 'directDebitStatus' => \prelevements::STATUS_VALID);
        $paramTypes  = array('daysInterval' => \PDO::PARAM_INT, 'projectStatus' => \PDO::PARAM_INT, 'directDebitStatus' => \PDO::PARAM_INT);

        $statement = $this->bdd->executeQuery($sql, $paramValues, $paramTypes);
        $result    = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }
}
