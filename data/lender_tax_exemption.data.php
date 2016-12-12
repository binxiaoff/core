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
class lender_tax_exemption extends lender_tax_exemption_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::lender_tax_exemption($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `lender_tax_exemption`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $result   = array();
        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $result = $this->bdd->query('SELECT COUNT(*) FROM `lender_tax_exemption` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_lender_tax_exemption')
    {
        $result = $this->bdd->query('SELECT * FROM `lender_tax_exemption` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_assoc($result) > 0);
    }

    /**
     * @param int $lenderId
     * @param string|null $year
     * @return array
     * @throws \Exception
     */
    public function getLenderExemptionHistory($lenderId, $year = null)
    {
        $bind = ['id_lender' => $lenderId];
        $type = ['id_lender' => \PDO::PARAM_INT];
        $sql  = '
            SELECT
              lte.*,
              u.firstname AS user_firstname,
              u.name AS user_name
            FROM
              lender_tax_exemption lte
              LEFT JOIN users u ON u.id_user = lte.id_user
            WHERE lte.id_lender = :id_lender
        ';
        if (false === is_null($year)) {
            $sql .= ' AND lte.year = :year';
            $bind['year'] = $year;
            $type['year'] = \PDO::PARAM_STR;
        }
        return $this->bdd->executeQuery($sql, $bind, $type)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
