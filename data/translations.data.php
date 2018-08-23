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
class translations extends translations_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::__construct($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `translations`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `translations` ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_translation')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `translations` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }

//TODO delete after front has been migrated completely AND function no longer used anyway
    public function selectFront($section, $id_langue)
    {
        if ('fr' == $id_langue) {
            $id_langue = 'fr_FR';
        }

        $sql      = 'SELECT * FROM translations WHERE section = "' . $section . '" AND locale = "' . $id_langue . '"';
        $resultat = $this->bdd->query($sql);
        $result   = array();

        while ($record = $this->bdd->fetch_array($resultat)) {
            $start                  = (isset($_SESSION['user']['id_user'], $_SESSION['modification']) && $_SESSION['user']['id_user'] != "" && $_SESSION['modification'] == 1 ? "<trad onclick='openTraduc(" . $record['id_texte'] . "); return false;'>" : "");
            $end                    = (isset($_SESSION['user']['id_user'], $_SESSION['modification']) && $_SESSION['user']['id_user'] != "" && $_SESSION['modification'] == 1 ? "</trad>" : "");
            $result[$record['name']] = $start . $record['translation'] . $end;
        }

        return $result;
    }
}
