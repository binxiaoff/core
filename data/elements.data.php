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

class elements extends elements_crud
{
    const TYPE_PDF_CGU = 183;

    function elements($bdd, $params = '')
    {
        parent::elements($bdd, $params);
    }

    function get($id, $field = 'id_element')
    {
        return parent::get($id, $field);
    }

    function update($cs = '')
    {
        parent::update($cs);
    }

    function delete($id, $field = 'id_element')
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
        $sql = 'SELECT * FROM `elements`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `elements` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    function exist($id, $field = 'id_element')
    {
        $sql    = 'SELECT * FROM `elements` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    //******************************************************************************************//
    //**************************************** AJOUTS ******************************************//
    //******************************************************************************************//

    // Récupération de la derniere position
    function getLastPosition($id, $champs)
    {
        $sql    = 'SELECT ordre FROM elements WHERE ' . $champs . ' = "' . $id . '" ORDER BY ordre DESC LIMIT 1';
        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    // Récupération de la position
    function getPosition($id_element, $id, $champs)
    {
        $sql    = 'SELECT ordre FROM elements WHERE ' . $champs . ' = "' . $id . '" AND id_element = ' . $id_element;
        $result = $this->bdd->query($sql);

        return (int) ($this->bdd->result($result, 0, 0));
    }

    // Monter un lien
    function moveUp($id_element, $id, $champs)
    {
        $position = $this->getPosition($id_element, $id, $champs);

        $sql = 'UPDATE elements SET ordre = ordre + 1 WHERE ' . $champs . ' = "' . $id . '" AND ordre < ' . $position . ' ORDER BY ordre DESC LIMIT 1';
        $this->bdd->query($sql);

        $sql = 'UPDATE elements SET ordre = ordre - 1 WHERE ' . $champs . ' = "' . $id . '" AND id_element = ' . $id_element;
        $this->bdd->query($sql);
        $this->reordre($id, $champs);
    }

    // Descendre un lien
    function moveDown($id_element, $id, $champs)
    {
        $position = $this->getPosition($id_element, $id, $champs);

        $sql = 'UPDATE elements SET ordre = ordre - 1 WHERE ' . $champs . ' = "' . $id . '" AND ordre > ' . $position . ' ORDER BY ordre ASC LIMIT 1';
        $this->bdd->query($sql);

        $sql = 'UPDATE elements SET ordre = ordre + 1 WHERE ' . $champs . ' = "' . $id . '" AND id_element = ' . $id_element;
        $this->bdd->query($sql);
        $this->reordre($id, $champs);
    }

    // Reordonner un menu
    function reordre($id, $champs)
    {
        $sql    = 'SELECT * FROM elements WHERE ' . $champs . ' = "' . $id . '" ORDER BY ordre ASC';
        $result = $this->bdd->query($sql);

        $i = 0;
        while ($record = $this->bdd->fetch_array($result)) {
            $sql1 = 'UPDATE elements SET ordre = ' . $i . ' WHERE id_element = ' . $record['id_element'];
            $this->bdd->query($sql1);
            $i++;
        }
    }
}
