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

class wallets_lines extends wallets_lines_crud
{
    /**
     * Types of financial operations
     * @var int
     */
    const TYPE_LENDER_SUBSCRIPTION = 10;
    const TYPE_BID                 = 20;
    const TYPE_MONEY_SUPPLY        = 30;
    const TYPE_REPAYMENT           = 40;

    const PHYSICAL = 1;
    const VIRTUAL  = 2;

    // The field is not used. We put always 1
    const STATUS_VALID = 1;

    public function __construct($bdd, $params = '')
    {
        parent::wallets_lines($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `wallets_lines`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `wallets_lines` ' . $where;

        $result = $this->bdd->query($sql);

        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_wallet_line')
    {
        $sql    = 'SELECT * FROM `wallets_lines` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);

        return ($this->bdd->fetch_array($result) > 0);
    }

    // retourne la moyenne des prets validés d'un preteur
    public function getSumDepot($id_lender, $type)
    {
        $sql = 'SELECT SUM(amount) as montant FROM wallets_lines WHERE id_lender = ' . $id_lender . ' AND type_financial_operation IN (' . $type . ') AND display = 0';

        $result  = $this->bdd->query($sql);
        $montant = $this->bdd->result($result, 0, 'montant');
        if ($montant == '') {
            $montant = 0;
        } else {
            $montant = $montant / 100;
        }
        return $montant;
    }
}
