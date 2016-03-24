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

class companies_bilans extends companies_bilans_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::companies_bilans($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM companies_bilans' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $result = $this->bdd->query('SELECT COUNT(*) FROM companies_bilans' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_bilan')
    {
        $result = $this->bdd->query('SELECT * FROM companies_bilans WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function calcultateFromBalance()
    {
        $oCompanyBalance = new company_balance($this->bdd);
        $aBalances       = $oCompanyBalance->getBalanceSheetsByAnnualAccount(array($this->id_bilan));

        $this->ca                          = $aBalances[$this->id_bilan]['FL'];
        $this->resultat_brute_exploitation = $aBalances[$this->id_bilan]['GG'] + $aBalances[$this->id_bilan]['GA'] + $aBalances[$this->id_bilan]['GB'] + $aBalances[$this->id_bilan]['GC'] + $aBalances[$this->id_bilan]['GD'] - $aBalances[$this->id_bilan]['FP'] - $aBalances[$this->id_bilan]['FQ'] + $aBalances[$this->id_bilan]['GE'];
        $this->resultat_exploitation       = $aBalances[$this->id_bilan]['GG'];
        $this->resultat_financier          = $aBalances[$this->id_bilan]['GV'];
        $this->produit_exceptionnel        = $aBalances[$this->id_bilan]['HA'] + $aBalances[$this->id_bilan]['HB'] + $aBalances[$this->id_bilan]['HC'];
        $this->charges_exceptionnelles     = $aBalances[$this->id_bilan]['HE'] + $aBalances[$this->id_bilan]['HF'] + $aBalances[$this->id_bilan]['HG'];
        $this->resultat_exceptionnel       = $this->produit_exceptionnel - $this->charges_exceptionnelles;
        $this->resultat_net                = $aBalances[$this->id_bilan]['HN'];
        $this->update();
    }
}
