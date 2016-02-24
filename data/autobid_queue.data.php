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

class autobid_queue extends autobid_queue_crud
{
    const TYPE_QUEUE_NEW    = 0;
    const TYPE_QUEUE_REJECTED = 1;
    const TYPE_QUEUE_BID  = 2;

    public function __construct($bdd, $params = '')
    {
        parent::autobid_queue($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `autobid_queue`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `autobid_queue` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_queue')
    {
        $sql    = 'SELECT * FROM `autobid_queue` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    public function getAutoBids($iPeriod, $sEvaluation, $fRate)
    {
        $sBasicQuery = 'SELECT a.*, la.id_client_owner as id_client
                   FROM autobid_queue aq
                   INNER JOIN autobid a ON a.id_lender = aq.id_lender
                   INNER JOIN autobid_periods ap ON ap.id_period = a.id_autobid_period
                   INNER JOIN lenders_accounts la ON la.id_lender_account = aq.id_lender
                   WHERE ' . $iPeriod . ' BETWEEN ap.min AND ap.max
                   AND ap.status = ' . \autobid_periods::STATUS_ACTIVE . '
                   AND a.evaluation = "' . $sEvaluation . '"
                   AND a.rate_min <= ' . $fRate . '
                   AND a.status = ' . \autobid::STATUS_ACTIVE;

        $sQuery  = 'SELECT * FROM ( '
                        . $sBasicQuery . '
                        AND aq.type in (' . self::TYPE_QUEUE_REJECTED . ', '. self::TYPE_QUEUE_NEW .')
                        ORDER BY aq.type, aq.id_queue ASC
                    ) rejected_new_queue

                    UNION

                    SELECT * FROM ( '
                        . $sBasicQuery . '
                        AND aq.type = ' . self::TYPE_QUEUE_BID . '
                        ORDER BY aq.id_queue DESC
                    ) bid_queue';
        $rQuery  = $this->bdd->query($sQuery);
        $aResult = array();
        while ($aRow = $this->bdd->fetch_array($rQuery)) {
            $aResult[] = $aRow;
        }
        return $aResult;
    }

    public function addToQueue($iLenderId, $iType)
    {
        $this->delete($iLenderId, 'id_lender');

        $this->id_lender = $iLenderId;
        $this->type    = $iType;
        $this->create();
    }
}