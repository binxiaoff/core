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

class factures extends factures_crud
{
    const TYPE_COMMISSION_FINANCEMENT = 1;
    const TYPE_COMMISSION_REMBOURSEMENT = 2;

    public function __construct($bdd, $params = '')
    {
        parent::factures($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `factures`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `factures` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_facture')
    {
        $sql    = 'SELECT * FROM `factures` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    public function fa()
    public function selectEcheancesRembAndNoFacture()
    public function selectEcheancesRembAndNoFacture()
    {
        $sql = '
            SELECT ee.id_project, p.slug, p.id_company, c.id_client_owner, cli.hash, ee.ordre
            FROM echeanciers_emprunteur ee
            LEFT JOIN projects p ON ee.id_project = p.id_project
            LEFT JOIN companies c ON p.id_company = c.id_company
            LEFT JOIN clients cli ON c.id_client_owner = cli.id_client
            WHERE ee.status_emprunteur = 1
                AND (SELECT e.status FROM echeanciers e WHERE e.id_project = ee.id_project AND e.ordre = ee.ordre LIMIT 1) = 1
                AND (SELECT f.date FROM factures f WHERE f.id_project = ee.id_project AND f.ordre = ee.ordre AND f.type_commission = 2 LIMIT 1) IS NULL
            ORDER BY ee.id_project , ee.ordre';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }
}
