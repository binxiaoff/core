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
class mail_queue extends mail_queue_crud
{
    const STATUS_PENDING    = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_SENT       = 2;
    const STATUS_ERROR      = -1;

    public function __construct($bdd, $params = '')
    {
        parent::mail_queue($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `mail_queue`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int)$this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `mail_queue` ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_queue')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `mail_queue` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }

    public function searchSentEmails($sFrom = null, $sTo = null, $sSubject = null, \DateTime $oDateStart = null, \DateTime $oDateEnd = null, $iLimit = null)
    {
        $sWhere = '';
        $sLimit = '';

        if (false === is_null($sFrom)) {
            $sWhere .= ' AND mt.exp_name LIKE "%' . $sFrom . '%" ';
        }

        if (false === is_null($sTo)) {
            $sWhere .= 'AND mg.recipient LIKE "%' . $sTo . '%"';
        }

        if (false === is_null($sSubject)) {
            $sWhere .= 'AND mt.subject LIKE "%' . $sSubject . '%"';
        }

        if (false === is_null($oDateStart)) {
            $sWhere .= 'AND mq.sent_at >= ' . $oDateStart->format('Y-m-d h:i:s');
        }

        if (false === is_null($oDateEnd)) {
            $sWhere .= 'AND mq.sent_at <= ' . $oDateEnd->format('Y-m-d h:i:s');
        }

        if (false === is_null($iLimit)) {
            $sLimit = ' LIMIT ' . $iLimit;
        }

        $sQuery = 'SELECT
                      mq.*,
                      mt.exp_name,
                      mt.subject,
                      mt.id_textemail
                    FROM
                      mail_queue mq
                    INNER JOIN mails_text mt ON mq.id_mail_text = mt.id_textemail
                    WHERE mq.status =' . self::STATUS_SENT . $sWhere . '
                    ORDER BY mq.sent_at DESC ' . $sLimit;

        return $this->bdd->executeQuery($sQuery);

    }

}
