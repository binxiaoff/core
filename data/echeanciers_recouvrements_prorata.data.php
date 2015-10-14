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

class echeanciers_recouvrements_prorata extends echeanciers_recouvrements_prorata_crud {

    function echeanciers_recouvrements_prorata($bdd, $params = '') {
        parent::echeanciers_recouvrements_prorata($bdd, $params);
    }

    function get($id, $field = 'id_echenacier_recouvrement_prorata') {
        return parent::get($id, $field);
    }

    function update($cs = '') {
        parent::update($cs);
    }

    function delete($id, $field = 'id_echenacier_recouvrement_prorata') {
        parent::delete($id, $field);
    }

    function create($cs = '') {
        $id = parent::create($cs);
        return $id;
    }

    function select($where = '', $order = '', $start = '', $nb = '') {
        if ($where != '')
            $where = ' WHERE ' . $where;
        if ($order != '')
            $order = ' ORDER BY ' . $order;
        $sql = 'SELECT * FROM `echeanciers_recouvrements_prorata`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    function counter($where = '') {
        if ($where != '')
            $where = ' WHERE ' . $where;

        $sql = 'SELECT count(*) FROM `echeanciers_recouvrements_prorata` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    function exist($id, $field = 'id_echenacier_recouvrement_prorata') {
        $sql = 'SELECT * FROM `echeanciers_recouvrements_prorata` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    function sumCapitalInterets($where = '') {
        if ($where != '')
            $where = ' WHERE ' . $where;

        $sql = '
            SELECT 
                sum(capital) as capital, 
                sum(interets) as interets,
                sum(prelevements_obligatoires) as prelevements_obligatoires,
                sum(retenues_source) as retenues_source,
                sum(csg) as csg,
                sum(prelevements_sociaux) as prelevements_sociaux,
                sum(contributions_additionnelles) as contributions_additionnelles,
                sum(prelevements_solidarite) as prelevements_solidarite,
                sum(crds) as crds
            FROM `echeanciers_recouvrements_prorata` ' . $where;
        
        $resultat = $this->bdd->query($sql);
        $result = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result['capital'] = round($record['capital']/100,2);
            $result['interets'] = round($record['interets']/100,2);
            $result['prelevements_obligatoires'] = $record['prelevements_obligatoires'];
            $result['retenues_source'] = $record['retenues_source'];
            $result['csg'] = $record['csg'];
            $result['prelevements_sociaux'] = $record['prelevements_sociaux'];
            $result['contributions_additionnelles'] = $record['contributions_additionnelles'];
            $result['prelevements_solidarite'] = $record['prelevements_solidarite'];
            $result['crds'] = $record['crds'];
        }
        return $result;
    }

}
