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

class bank_unilend extends bank_unilend_crud
{
    const TYPE_THREE_PERCENT_VAT               = 0;
    const TYPE_REPAYMENT_BORROWER              = 1;
    const TYPE_REPAYMENT_LENDER                = 2;
    const TYPE_DEBIT_UNILEND                   = 3;
    const TYPE_UNILEND_WELCOME_OFFER_PATRONAGE = 4;

    const STATUS_CREDITED_ON_UNILEND_ACCOUNT = 0;
    const STATUS_CREDITED_ON_LENDER_ACCOUNT  = 1;
    const STATUS_DEBITED_UNILEND_ACCOUNT     = 3;
    //NOTE : there is not status value 2

    public function __construct($bdd, $params = '')
    {
        parent::bank_unilend($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `bank_unilend`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `bank_unilend` ' . $where;

        $result = $this->bdd->query($sql);

        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_unilend')
    {
        $sql    = 'SELECT * FROM `bank_unilend` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);

        return ($this->bdd->fetch_array($result) > 0);
    }

    public function sum($date, $where = '', $sum = 'montant')
    {
        if ($where != '') {
            $where = ' AND ' . $where;
        }

        $sql = 'SELECT SUM(' . $sum . ') FROM `bank_unilend` WHERE LEFT(added,10) = "' . $date . '" ' . $where;

        $result = $this->bdd->query($sql);
        $solde  = $this->bdd->result($result, 0, 0);
        if ($solde == '') {
            $solde = 0;
        } else {
            $solde = ($solde / 100);
        }

        return $solde;
    }

    public function sumMontant($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT SUM(montant) FROM `bank_unilend` ' . $where;

        $result = $this->bdd->query($sql);

        return (int)($this->bdd->result($result, 0, 0));
    }

    public function sumMontantEtat($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT SUM(etat) FROM `bank_unilend` ' . $where;

        $result = $this->bdd->query($sql);

        return (int)($this->bdd->result($result, 0, 0));
    }

    public function sumMontantByDay($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT SUM(montant) as montant,SUM(etat) as etat, added FROM `bank_unilend` ' . $where . ' GROUP by LEFT(added,10)';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }

        return $result;
    }

    public function sumMontantByDayMonths($where = '', $month, $year)
    {
        if ($where != '') {
            $where = ' AND ' . $where;
        }

        $sql = 'SELECT SUM(montant) as montant,SUM(etat) as etat, LEFT(added,10) as date FROM `bank_unilend` WHERE LEFT(added,7) = "' . $year . '-' . $month . '" ' . $where . ' GROUP BY date';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[$record['date']]['montant'] = str_replace('-', '', $record['montant'] / 100);
            $result[$record['date']]['etat']    = $record['etat'] / 100;
        }

        return $result;
    }

    public function ListEcheancesByDayMonths($where = '', $month, $year)
    {
        if ($where != '') {
            $where = ' AND ' . $where;
        }

        $sql = 'SELECT LEFT(added,10) as date, id_echeance_emprunteur FROM `bank_unilend` WHERE LEFT(added,7) = "' . $year . '-' . $month . '" ' . $where . ' ORDER BY date';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        $ladate   = '';
        while ($record = $this->bdd->fetch_array($resultat)) {
            if ($record['id_echeance_emprunteur'] != '0') {
                if ($record['date'] == $ladate) {
                    $result[$ladate] .= ',' . $record['id_echeance_emprunteur'];
                } else {
                    $result[$record['date']] = $record['id_echeance_emprunteur'];
                }

                $ladate = $record['date'];
            }
        }

        return $result;
    }
}
