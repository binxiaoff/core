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

class prospects extends prospects_crud
{
    function prospects($bdd, $params = '')
    {
        parent::prospects($bdd, $params);
    }

    function get($id, $field = 'id_prospect')
    {
        return parent::get($id, $field);
    }

    function update($cs = '')
    {
        parent::update($cs);
    }

    function delete($id, $field = 'id_prospect')
    {
        parent::delete($id, $field);
    }

    function create($cs = '')
    {
        $id = parent::create($cs);
        return $id;
    }

    function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `prospects`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT count(*) FROM `prospects` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    function exist($id, $field = 'id_prospect')
    {
        $sql    = 'SELECT * FROM `prospects` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    function update_added($date, $id_prospect)
    {
        $sql = "UPDATE prospects SET added = '" . $date . "' WHERE id_prospect = " . $id_prospect;
        $this->bdd->query($sql);
    }

    public function getProspectsSalesForce()
    {
        $sQuery = "SELECT id_prospect,
                    id_langue,
                    REPLACE(source,',','') as 'source',
                    REPLACE(source2,',','') as 'source2',
                    REPLACE(source3,',','') as 'source3',
                    REPLACE(nom,',','') as 'nom',
                    REPLACE(prenom,',','') as 'prenom',
                    email,
                    CASE added
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE added
                    END as 'added',
                    CASE updated
                      WHEN '0000-00-00 00:00:00' then ''
                      ELSE updated
                    END as 'updated'
                  FROM prospects p";

        return $this->bdd->executeQuery($sQuery);
    }
}
