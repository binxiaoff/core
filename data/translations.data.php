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
        parent::translations($bdd, $params);
    }

    public function create($cs='')
    {
        $this->bdd->insert('translations', array('lang' => $this->lang, 'section' => $this->section, 'name' => $this->name, 'translation' => $this->translation));
        $this->get($this->id_translation,'id_translation');
        return $this->id_translation;
    }

    public function update($cs='')
    {
        $this->bdd->update('translations', array('translation' => $this->translation), array('lang' => $this->lang, 'section' => $this->section, 'name' => $this->name, ));
        $this->get($this->id_translation,'id_translation');
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

    public function getAllTranslationMessages($sLanguage)
    {
        $sQuery = 'SELECT * FROM translations WHERE lang = ? ';
        $oStatement = $this->bdd->executeQuery($sQuery, array($sLanguage));
        $aTranslations = array();
        while ($aRow = $oStatement->fetch(\PDO::FETCH_ASSOC)) {
            $aTranslations[] = $aRow;
        }

        return $aTranslations;
    }

    public function selectSections($sLanguage = 'fr_FR')
    {
        $sQuery     = 'SELECT DISTINCT section, COUNT(translation) FROM translations WHERE lang = ? GROUP BY section ORDER BY section ASC ';
        $oStatement = $this->bdd->executeQuery($sQuery, array($sLanguage));
        $aSections  = array();
        while ($aRow = $oStatement->fetch(\PDO::FETCH_ASSOC)) {
            $aSections[] = $aRow;
        }

        return $aSections;
    }

    public function selectNamesForSection($sSection)
    {
        $sQuery     = 'SELECT DISTINCT name FROM translations WHERE section = ? ORDER BY name ASC';
        $oStatement = $this->bdd->executeQuery($sQuery, array($sSection));
        $aNames  = array();
        while ($aRow = $oStatement->fetch(\PDO::FETCH_ASSOC)) {
            $aNames[] = $aRow;
        }

        return $aNames;
    }

    public function selectTranslation($sSection, $sName)
    {
        $sQuery     = 'SELECT translation FROM translations WHERE section = ? AND name = ?';
        $oStatement = $this->bdd->executeQuery($sQuery, array($sSection, $sName));

        return $oStatement->fetchColumn(0);
    }

    public function getAllTranslationsForSection($sSection, $sLanguage)
    {
        $sQuery = 'SELECT * FROM translations WHERE section = ? AND lang = ?';
        $oStatement = $this->bdd->executeQuery($sQuery, array($sSection, $sLanguage));
        $aTranslations  = array();
        while ($aRow = $oStatement->fetch(\PDO::FETCH_ASSOC)) {
            $aTranslations[] = $aRow;
        }

        return $aTranslations;
    }

    //TODO delete after front has been migrated completely AND function no longer used anyway
    public function selectFront($section, $id_langue)
    {
        if ('fr' == $id_langue) {
            $id_langue = 'fr_FR';
        }

        $sql      = 'SELECT * FROM translations WHERE section = "' . $section . '" AND lang = "' . $id_langue . '"';
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
