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

class projects_remb extends projects_remb_crud
{
    const STATUS_ERROR                     = -1;
    const STATUS_PENDING                   = 0;
    const STATUS_REFUNDED                  = 1;
    const STATUS_REJECTED                  = 2;
    const STATUS_AUTOMATIC_REFUND_DISABLED = 4;

    public function __construct($bdd, $params = '')
    {
        parent::projects_remb($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `projects_remb`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $result   = array();
        $resultat = $this->bdd->query($sql);
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

        $result = $this->bdd->query('SELECT COUNT(*) FROM `projects_remb` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_project_remb')
    {
        $result = $this->bdd->query('SELECT * FROM `projects_remb` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function getProjectsToRepay(\DateTime $oRepaymentDate = null, $iLimit = null)
    {
        if (null === $oRepaymentDate) {
            $oRepaymentDate = new \DateTime();
        }
        $sQuery = '
            SELECT r.*
            FROM projects_remb r
            INNER JOIN projects p ON r.id_project = p.id_project
            WHERE p.remb_auto = 0
                AND r.status = ' . \projects_remb::STATUS_PENDING . '
                AND DATE(r.date_remb_preteurs) <= "' . $oRepaymentDate->format('Y-m-d') . '"';

        if (null !== $iLimit) {
            $sQuery .= 'LIMIT ' . $iLimit;
        }

        $aResult  = array();
        $arResult = $this->bdd->query($sQuery);
        while ($aRecord = $this->bdd->fetch_assoc($arResult)) {
            $aResult[] = $aRecord;
        }
        return $aResult;
    }
}
