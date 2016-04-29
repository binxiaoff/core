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
class --classe--
{
    /** @var \Unilend\Library\Bridge\Doctrine\DBAL\Connection */
    protected $bdd;

    --declaration--

    public function --table--($bdd,$params = '')
    {
        $this->bdd = $bdd;
        if ($params == '') {
            $params = array();
        }
        $this->params = $params;
        --initialisation--
    }

    public function get($list_field_value)
    {
        $list = '';
        foreach ($list_field_value as $champ => $valeur) {
            $list .= ' AND `' . $champ . '` = "' . $valeur . '" ';
        }

        $sql    = 'SELECT * FROM `--table--` WHERE 1=1 ' . $list . ' ';
        $result = $this->bdd->query($sql);

        if ($this->bdd->num_rows($result) == 1) {
            $record = $this->bdd->fetch_array($result);

            --remplissage--
            return true;
        } else {
            $this->unsetData();
            return false;
        }
    }

    public function update($list_field_value)
    {
        --escapestring--

        $list = '';
        foreach ($list_field_value as $champ => $valeur) {
            $list .= ' AND `' . $champ . '` = "' . $valeur . '" ';
        }

        $sql = 'UPDATE `--table--` SET --updatefields-- WHERE 1=1 ' . $list . ' ';
        $this->bdd->query($sql);

        --controleslugmulti--

        $this->get($list_field_value);
    }

    public function delete($list_field_value)
    {
        $list = '';
        foreach ($list_field_value as $champ => $valeur) {
            $list .= ' AND `' . $champ . '` = "' . $valeur . '" ';
        }

        $sql = 'DELETE FROM `--table--` WHERE 1=1 ' . $list . ' ';
        $this->bdd->query($sql);
    }

    public function create($list_field_value)
    {
        --escapestring--

        $sql = 'INSERT INTO `--table--`(--clist--) VALUES(--cvalues--)';
        $this->bdd->query($sql);

        --controleslugmulti--

        $this->get($list_field_value);
    }

    public function unsetData()
    {
        --initialisation--
    }

    public function multiInsert($aData)
    {
        $aInsert = array();
        $sColumnLabel = implode(',', array_keys($aData[0]));
        foreach( $aData as $aRow ) {
            $aInsertRow = array();
            foreach ($aRow as $mColumn) {
                $aInsertRow[] = '"' . $mColumn . '"';
            }
            $aInsert[] = '(' . implode(',', $aInsertRow) .')';
        }

        $sInsert = 'INSERT INTO `--table--` (' . $sColumnLabel . ') VALUES '.implode(',', $aInsert);

        $this->bdd->query($sInsert);
    }
}
