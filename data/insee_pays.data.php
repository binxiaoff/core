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

class insee_pays extends insee_pays_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::insee_pays($bdd, $params);
    }

    public function get($id, $field = 'id_insee_pays')
    {
        return parent::get($id, $field);
    }

    public function delete($id, $field = 'id_insee_pays')
    {
        parent::delete($id, $field);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `insee_pays`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `insee_pays` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_insee_pays')
    {
        $sql    = 'SELECT * FROM `insee_pays` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function getByCountryIso($sCodeIso)
    {
        $result = $this->bdd->query('SELECT * FROM insee_pays WHERE CODEISO2 LIKE "' . $sCodeIso . '"');

        if ($this->bdd->num_rows() == 1) {
            $record = $this->bdd->fetch_array($result);

            $this->CODEISO2 = $record['CODEISO2'];
            $this->COG      = $record['COG'];
            $this->ACTUAL   = $record['ACTUAL'];
            $this->CAPAY    = $record['CAPAY'];
            $this->CRPAY    = $record['CRPAY'];
            $this->ANI      = $record['ANI'];
            $this->LIBCOG   = $record['LIBCOG'];
            $this->LIBENR   = $record['LIBENR'];
            $this->ANCNOM   = $record['ANCNOM'];

            return true;
        } else {
            $this->unsetData();
            return false;
        }
    }
}

