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

class platform_account_unilend extends platform_account_unilend_crud
{
    const TYPE_COMMISSION_PROJECT  = 1;
    const TYPE_COMMISSION_DUE_DATE = 2;
    const TYPE_WITHDRAW            = 3;

    public function __construct($bdd, $params = '')
    {
        parent::platform_account_unilend($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `platform_account_unilend`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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
        $result = $this->bdd->query('SELECT count(*) FROM `platform_account_unilend` ' . $where);

        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id')
    {
        $result = $this->bdd->query('SELECT * FROM `platform_account_unilend` WHERE ' . $field . '="' . $id . '"');

        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    public function addDueDateCommssion($iBorrowerDueDateId)
    {
        if (false === $this->get($iBorrowerDueDateId . '" AND type = "2', 'id_echeance_emprunteur')) {
            $sql = 'INSERT INTO platform_account_unilend (id_echeance_emprunteur, id_project, amount, type, added, updated)
                    SELECT DISTINCT ee.id_echeancier_emprunteur, ee.id_project, ee.commission + ee.tva, 2, ee.updated, now()
                    FROM echeanciers_emprunteur ee
                    INNER JOIN echeanciers ep
                        ON (ee.id_project = ep.id_project AND ee.ordre = ep.ordre)
                    WHERE ee.status_emprunteur = 1
                    AND ep.status = 1
                    AND ee.status_ra = 0
                    AND id_echeancier_emprunteur=' . $iBorrowerDueDateId;

            $this->bdd->query($sql);

            $this->id = $this->bdd->insert_id();

            $this->get($this->id, 'id');

            return $this->id;
        } else {
            return $this->id;
        }
    }

    public function getBalance()
    {
        $result = $this->bdd->query('SELECT SUM(amount) FROM `platform_account_unilend`');

        return (int)($this->bdd->result($result, 0, 0));
    }
}