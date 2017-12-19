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

class villes extends villes_crud
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
        $sql = 'SELECT * FROM `villes`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function selectCp($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT DISTINCT(cp) FROM `villes`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
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

        $sql = 'SELECT COUNT(*) FROM `villes` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_ville')
    {
        $sql    = 'SELECT * FROM `villes` WHERE ' . $field . ' = "' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_assoc($result) > 0);
    }

    public function getByName($name)
    {
        $sql    = 'SELECT * FROM villes WHERE ville LIKE "' . $name . '"';
        $result = $this->bdd->query($sql);

        if ($this->bdd->num_rows($result) === 1) {
            $record = $this->bdd->fetch_assoc($result);
            return $this->get($record['id_ville']);
        }

        return false;
    }

    public function generateCodeInsee($sCodeDepartement, $sCodeCommune)
    {
        $sCodeDepartement = str_pad($sCodeDepartement, 2, 0, STR_PAD_LEFT);
        $sPadLength       = 5 - strlen($sCodeDepartement);
        $sCodeCommune     = str_pad($sCodeCommune, $sPadLength, 0, STR_PAD_LEFT);

        return $sCodeDepartement . $sCodeCommune;
    }

    public function lookupCities($search, $fields = ['ville', 'cp'], $includeOldCity = false)
    {
        if (empty($fields)) {
            return array();
        }

        $starting         = '';
        $containing       = '';
        $search           = $this->cleanLookupTerm($search);
        $searchStarting   = $search . '%';
        $searchContaining = '%' . $search . '%';

        foreach ($fields as $index => $field) {
            if ($index > 0) {
                $starting .= ' OR ';
                $containing .= ' OR ';
            }
            $starting .= $field . ' LIKE :starting';
            $containing .= $field . ' LIKE :containing';
        }

        $oldCity = '';
        if (false === $includeOldCity) {
            $oldCity = 'AND active = 1';
        }

        $query = '
            SELECT * FROM (
                SELECT id_ville, ville, cp, insee, num_departement
                FROM villes
                WHERE (' . $starting . ') ' . $oldCity . '
                ORDER BY ville ASC
            ) start_by
            UNION
            SELECT * FROM (
                SELECT id_ville, ville, cp, insee, num_departement
                FROM villes
                WHERE (' . $containing . ') ' . $oldCity . '
                ORDER BY ville ASC
            ) contain
            LIMIT 25';

        try {
            $statement = $this->bdd->executeQuery($query,
                ['starting' => $searchStarting, 'containing' => $searchContaining],
                ['starting' => \PDO::PARAM_STR, 'containing' => \PDO::PARAM_STR],
                new \Doctrine\DBAL\Cache\QueryCacheProfile(300, md5(__METHOD__))
            );
            $result = $statement->fetchAll(PDO::FETCH_ASSOC);
            $statement->closeCursor();
            return $result;
        } catch (\Doctrine\DBAL\DBALException $execption) {
            return [];
        }
    }

    public function cleanLookupTerm($sTerm)
    {
        $sTerm = str_replace(' ', '-', strtoupper($sTerm));
        // Replace ST, SNT with SAINT
        $sTerm = preg_replace('/(^|.+-)((ST)|(SNT))(-)(.+)/', '$1SAINT$5$6', $sTerm);
        // Replace STE with SAINTE
        $sTerm = preg_replace('/(^|.+-)(STE)(-)(.+)/', '$1SAINTE$3$4', $sTerm);
        // Remove le la les l' from the beginning of the term
        $sTerm = preg_replace('/^(LE-|LA-|LES-|L\')(.+)/', '$2', $sTerm);

        return $sTerm;
    }

    public function getInseeCode($sPostCode, $City)
    {
        $sql = 'SELECT * FROM `villes` WHERE cp = "' . $sPostCode . '"';
        $oQuery = $this->bdd->query($sql);

        // If we found more then one city, we retry the query with the name.
        if ($this->bdd->num_rows($oQuery) > 1) {
            $sql = 'SELECT * FROM `villes` WHERE cp = "' . $sPostCode . '" AND ville = "' . $City .'"';
            $oQuery = $this->bdd->query($sql);
        }

        if ($this->bdd->num_rows($oQuery) == 1) {
            $aVille = $this->bdd->fetch_assoc($oQuery);

            if (isset($aVille['insee']) && '' !== $aVille['insee']) {
                return $aVille['insee'];
            }
        }

        return false;
    }
}
