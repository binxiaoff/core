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

class companies_actif_passif extends companies_actif_passif_crud
{

    public function companies_actif_passif($bdd, $params = '')
    {
        parent::companies_actif_passif($bdd, $params);
    }

    public function get($id, $field = 'id_actif_passif')
    {
        return parent::get($id, $field);
    }

    public function update($cs = '')
    {
        parent::update($cs);
    }

    public function delete($id, $field = 'id_actif_passif')
    {
        parent::delete($id, $field);
    }

    public function create($cs = '')
    {
        $id = parent::create($cs);
        return $id;
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `companies_actif_passif`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `companies_actif_passif` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_actif_passif')
    {
        $sql    = 'SELECT * FROM `companies_actif_passif` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    public function yearExist($iCompanyId, $iYear)
    {
        $iCompanyId = $this->bdd->escape_string($iCompanyId);
        $iYear      = $this->bdd->escape_string($iYear);

        $sSql = "SELECT EXISTS(SELECT 1 FROM companies_actif_passif WHERE id_company = $iCompanyId AND annee = $iYear) as exist_active";

        $rResult = $this->bdd->query($sSql);
        $aResult = $this->bdd->fetch_array($rResult);
        return $aResult['exist_active'] == 1;
    }
}