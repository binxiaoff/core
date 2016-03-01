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

class autobid extends autobid_crud
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE   = 1;
    const STATUS_ARCHIVED = 2;

    public function __construct($bdd, $params = '')
    {
        parent::autobid($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `autobid`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `autobid` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_autobid')
    {
        $sql    = 'SELECT * FROM `autobid` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    public function getValidationDate($iLenderId)
    {
        $rResult = $this->bdd->query('SELECT MAX(`updated`) FROM `autobid` WHERE id_lender = ' . $iLenderId . ' AND status != ' . self::STATUS_ARCHIVED);
        return $this->bdd->result($rResult, 0, 0);
    }

    public function sumAmount($sEvaluation, $iDuration)
    {
        $sQuery  = 'SELECT SUM(`amount`)
                   FROM `autobid` a
                   INNER JOIN autobid_periods ap ON ap.id_period = a.id_autobid_period
                   WHERE ' . $iDuration . ' BETWEEN ap.min AND ap.max
                   AND ap.status = ' . \autobid_periods::STATUS_ACTIVE . '
                   AND a.status = ' . self::STATUS_ACTIVE . '
                   AND a.evaluation = "' . $sEvaluation . '"';
        $rResult = $this->bdd->query($sQuery);
        return $this->bdd->result($rResult, 0, 0);
    }

    public function getSettings($iLenderId = null, $sEvaluation = null, $iAutoBidPeriodId = null, $aStatus = array(\autobid::STATUS_ACTIVE), $sOrder = null)
    {
        $sWhereLender     = null === $iLenderId ? '' : ' AND a.id_lender = ' . $iLenderId;
        $sWhereEvaluation = null === $sEvaluation ? '' : ' AND a.evaluation = "' . $sEvaluation . '"';
        $sWherePeriod     = null === $iAutoBidPeriodId ? '' : ' AND a.id_autobid_period = ' . $iAutoBidPeriodId;
        $sOrderBy         = null === $sOrder ? '' : ' ORDER BY ' . $sOrder;

        $sQuery = 'SELECT a.*
                   FROM autobid a
                   INNER JOIN autobid_periods ap ON ap.id_period = a.id_autobid_period
                   WHERE ap.status = ' . \autobid_periods::STATUS_ACTIVE . '
                   AND a.status in (' . implode($aStatus, ',') . ')' . $sWhereLender . $sWhereEvaluation . $sWherePeriod . $sOrderBy;

        $aAutoBids = array();
        $rResult   = $this->bdd->query($sQuery);
        while ($aRecord = $this->bdd->fetch_assoc($rResult)) {
            $aAutoBids[] = $aRecord;
        }
        return $aAutoBids;
    }
}