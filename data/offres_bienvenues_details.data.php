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

class offres_bienvenues_details extends offres_bienvenues_details_crud
{

    const STATUS_NEW      = 0;
    const STATUS_USED     = 1;
    const STATUS_CANCELED = 2;

    const TYPE_OFFER   = 0;
    const TYPE_CUT     = 1;
    const TYPE_PAYBACK = 2;

    public function __construct($bdd, $params = '')
    {
        parent::offres_bienvenues_details($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `offres_bienvenues_details`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `offres_bienvenues_details` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function sum($where = '', $champ)
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT SUM(' . $champ . ') FROM `offres_bienvenues_details` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_offre_bienvenue_detail')
    {
        $sql    = 'SELECT * FROM `offres_bienvenues_details` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function getUnusedWelcomeOffers(\DateTime $date)
    {
        $query = '
                SELECT obd.*
                FROM offres_bienvenues_details obd
                  INNER JOIN lenders_accounts la ON obd.id_client = la.id_client_owner
                WHERE obd.status = 0 
                  AND DATE(obd.added) < :dateLimit
                  AND 0 < (SELECT count(*) FROM bids b WHERE b.id_lender_account = la.id_lender_account AND b.status != ' . \bids::STATUS_BID_REJECTED . ')';

        return $this->bdd->executeQuery($query, ['dateLimit' => $date->format('Y-m-d')])->fetchAll(PDO::FETCH_ASSOC);
    }

}
