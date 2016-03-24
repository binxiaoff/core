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

class mails_filer extends mails_filer_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::mails_filer($bdd, $params);
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
        $resultat = $this->bdd->query('SELECT * FROM mails_filer' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : '')));
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

        $result = $this->bdd->query('SELECT COUNT(*) FROM mails_filer ' . $where);

        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_filermails')
    {
        $result = $this->bdd->query('SELECT * FROM mails_filer WHERE ' . $field . ' = "' . $id . '"');

        return ($this->bdd->fetch_array($result) > 0);
    }

    public function getListOfEmails($sEmail, $sStartDate = '2013-01-01', $sEndDate = null)
    {
        if (null === $sEndDate) {
            $sEndDate = 'NOW()';
        } else {
            $sEndDate = str_pad($sEndDate, 12, '"', STR_PAD_BOTH);
        }

        $sql = '
            SELECT
                mf.id_filermails,
                mt.name,
                mf.subject,
                mf.added
            FROM mails_filer mf
            LEFT JOIN mails_text mt ON mf.id_textemail = mt.id_textemail
            WHERE email_nmp LIKE "' . $sEmail . '"
            AND DATE(mf.added) BETWEEN "' . $sStartDate . '" AND ' . $sEndDate . '
                AND (mf.to = "" OR mf.to IS NULL)
            ORDER BY mf.added DESC';

        $result   = array();
        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }
}
