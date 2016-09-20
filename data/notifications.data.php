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

class notifications extends notifications_crud
{
    const TYPE_BID_REJECTED                   = 1;
    const TYPE_REPAYMENT                      = 2;
    const TYPE_BID_PLACED                     = 3;
    const TYPE_LOAN_ACCEPTED                  = 4;
    const TYPE_BANK_TRANSFER_CREDIT           = 5;
    const TYPE_CREDIT_CARD_CREDIT             = 6;
    const TYPE_DEBIT                          = 7;
    const TYPE_NEW_PROJECT                    = 8;
    const TYPE_PROJECT_PROBLEM                = 9;
    const TYPE_PROJECT_PROBLEM_REMINDER       = 10;
    const TYPE_PROJECT_RECOVERY               = 11;
    const TYPE_PROJECT_PRECAUTIONARY_PROCESS  = 12;
    const TYPE_PROJECT_RECEIVERSHIP           = 13;
    const TYPE_PROJECT_COMPULSORY_LIQUIDATION = 14;
    const TYPE_PROJECT_FAILURE                = 15;
    const TYPE_AUTOBID_BALANCE_LOW            = 16;
    const TYPE_AUTOBID_BALANCE_INSUFFICIENT   = 17;
    const TYPE_AUTOBID_FIRST_ACTIVATION       = 18;

    const STATUS_READ   = 1;
    const STATUS_UNREAD = 0;

    public function __construct($bdd, $params = '')
    {
        parent::notifications($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `notifications`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $result = $this->bdd->query('SELECT COUNT(*) FROM `notifications` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_notification')
    {
        $sql    = 'SELECT * FROM `notifications` WHERE ' . $field . ' = "' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_assoc($result) > 0);
    }
}
