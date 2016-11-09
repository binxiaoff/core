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

class lenders_imposition_history extends lenders_imposition_history_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::lenders_imposition_history($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql      = 'SELECT * FROM `lenders_imposition_history`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));
        $result   = array();
        $resultat = $this->bdd->query($sql);
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

        $result = $this->bdd->query('SELECT COUNT(*) FROM lenders_imposition_history ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_lenders_imposition_history')
    {
        $result = $this->bdd->query('SELECT * FROM lenders_imposition_history WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }

    /**
     * @param int $lenderId
     * @return bool
     */
    public function loadLastTaxationHistory($lenderId)
    {
        $sQuery = '
            SELECT MAX(id_lenders_imposition_history)
            FROM lenders_imposition_history
            WHERE id_lender = ' . $lenderId;
        return $this->get($this->bdd->result($this->bdd->query($sQuery), 0));
    }

    /**
     * @param int $lenderId
     * @return array
     * @throws Exception
     */
    public function getTaxationHistory($lenderId)
    {
        $sql = '
        SELECT
          lih.*,
          p.fr AS country_name,
          u.firstname AS user_firstname,
          u.name AS user_name
        FROM lenders_imposition_history lih
          INNER JOIN pays_v2 p ON p.id_pays = lih.id_pays
          INNER JOIN users u ON u.id_user = lih.id_user
        WHERE lih.id_lender = :id_lender
        ORDER BY lih.added DESC
        ';

        return $this->bdd->executeQuery($sql, ['id_lender' => $lenderId], ['id_lender' => \PDO::PARAM_INT])->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTaxationSituationAtDate($lenderId, $date)
    {
        $query = 'SELECT id_pays, resident_etranger FROM lenders_imposition_history
                        WHERE id_lender = :id_lender
                        AND added <= :date
                        ORDER BY added DESC LIMIT 1';

        return $this->bdd->executeQuery($query, ['id_lender' => $lenderId, 'date' => $date], ['id_lender' => \PDO::PARAM_INT, 'date' => \PDO::PARAM_STR])->fetchAll(\PDO::FETCH_ASSOC);
    }
}
