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

class autobid_periods extends autobid_periods_crud
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE   = 1;

    const PERIOD_3_12    = 1;
    const PERIOD_18_24   = 2;
    const PERIOD_36      = 3;
    const PERIOD_48_60   = 4;
    const PERIOD_60_PLUS = 5;

    public function __construct($bdd, $params = '')
    {
        parent::autobid_periods($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `autobid_periods`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `autobid_periods` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_period')
    {
        $sql    = 'SELECT * FROM `autobid_periods` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    public function getDurations($periodId)
    {
        $sQuery = 'SELECT min, max FROM autobid_periods WHERE id_period = :periodId';
        try {
            $mDurations = $this->bdd->executeQuery($sQuery, array('periodId' => $periodId), array('periodId' => \PDO::PARAM_INT), new \Doctrine\DBAL\Cache\QueryCacheProfile(300, md5(__METHOD__)))
                ->fetch(PDO::FETCH_ASSOC);

            if (true === empty($mDurations)) {
                return false;
            }
        } catch (\Doctrine\DBAL\DBALException $ex) {
            return false;
        }

        return $mDurations;
    }

    public function getPeriod($iDuration, $iStatus = self::STATUS_ACTIVE)
    {
        $rQuery = $this->bdd->query('SELECT * FROM `autobid_periods` WHERE ' . $iDuration . ' BETWEEN `min` AND `max` AND `status` = ' . $iStatus);
        return $this->bdd->fetch_assoc($rQuery);
    }
}