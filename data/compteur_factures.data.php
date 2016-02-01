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

class compteur_factures extends compteur_factures_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::compteur_factures($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $result   = array();
        $resultat = $this->bdd->query('SELECT * FROM compteur_factures' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : '')));
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

        $result = $this->bdd->query('SELECT COUNT(*) FROM compteur_factures ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_compteur_facture')
    {
        $result = $this->bdd->query('SELECT * FROM compteur_factures WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    /**
     * @param int $iProjectId
     * @param string $sDate
     * @return int
     */
    public function compteurJournalier($iProjectId, $sDate)
    {
        $sDate   = date('Y-m-d', strtotime($sDate));
        $rResult = $this->bdd->query('SELECT IFNULL(MAX(ordre) + 1, 1) FROM compteur_factures WHERE `date` = "' . $sDate . '"');

        $this->id_project = $iProjectId;
        $this->ordre      = $this->bdd->result($rResult, 0, 0);
        $this->date       = $sDate;
        $this->create();

        return $this->ordre;
    }
}
