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

use Unilend\core\Loader;

class companies_actif_passif extends companies_actif_passif_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::companies_actif_passif($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM companies_actif_passif' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $result = $this->bdd->query('SELECT COUNT(*) FROM companies_actif_passif' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_actif_passif')
    {
        $result = $this->bdd->query('SELECT * FROM companies_actif_passif WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_assoc($result) > 0);
    }

    public function calcultateFromBalance()
    {
        /** @var \settings $oSetting */
        $oSetting = Loader::loadData('settings');
        $oSetting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $aFundedCompanies = explode(',', $oSetting->value);

        /** @var \companies_bilans $oCompanyAnnualAccounts */
        $oCompanyAnnualAccounts = Loader::loadData('companies_bilans');
        $oCompanyAnnualAccounts->get($this->id_bilan);

        if (in_array($oCompanyAnnualAccounts->id_company, $aFundedCompanies)) {
            return;
        }

        /** @var \company_balance $oCompanyBalance */
        $oCompanyBalance = Loader::loadData('company_balance');
        $aBalances       = $oCompanyBalance->getBalanceSheetsByAnnualAccount(array($this->id_bilan));

        $this->immobilisations_corporelles        = $aBalances[$this->id_bilan]['AN'] + $aBalances[$this->id_bilan]['AP'] + $aBalances[$this->id_bilan]['AR'] + $aBalances[$this->id_bilan]['AT'] + $aBalances[$this->id_bilan]['AV'] + $aBalances[$this->id_bilan]['AX'];
        $this->immobilisations_incorporelles      = $aBalances[$this->id_bilan]['AB'] + $aBalances[$this->id_bilan]['AD'] + $aBalances[$this->id_bilan]['AF'] + $aBalances[$this->id_bilan]['AH'] + $aBalances[$this->id_bilan]['AJ'] + $aBalances[$this->id_bilan]['AL'];
        $this->immobilisations_financieres        = $aBalances[$this->id_bilan]['CS'] + $aBalances[$this->id_bilan]['CU'] + $aBalances[$this->id_bilan]['BB'] + $aBalances[$this->id_bilan]['BD'] + $aBalances[$this->id_bilan]['BF'] + $aBalances[$this->id_bilan]['BH'];
        $this->stocks                             = $aBalances[$this->id_bilan]['BL'] + $aBalances[$this->id_bilan]['BN'] + $aBalances[$this->id_bilan]['BP'] + $aBalances[$this->id_bilan]['BR'] + $aBalances[$this->id_bilan]['BT'];
        $this->creances_clients                   = $aBalances[$this->id_bilan]['BV'] + $aBalances[$this->id_bilan]['BX'] + $aBalances[$this->id_bilan]['BZ'] + $aBalances[$this->id_bilan]['CB'];
        $this->disponibilites                     = $aBalances[$this->id_bilan]['CF'];
        $this->valeurs_mobilieres_de_placement    = $aBalances[$this->id_bilan]['CD'];
        $this->comptes_regularisation_actif       = $aBalances[$this->id_bilan]['CH'] + $aBalances[$this->id_bilan]['CW'] + $aBalances[$this->id_bilan]['CM'] + $aBalances[$this->id_bilan]['CN'];
        $this->capitaux_propres                   = $aBalances[$this->id_bilan]['DL'] + $aBalances[$this->id_bilan]['DO'];
        $this->provisions_pour_risques_et_charges = $aBalances[$this->id_bilan]['CK'] + $aBalances[$this->id_bilan]['DR'];
        $this->amortissement_sur_immo             = $aBalances[$this->id_bilan]['BK'];
        $this->dettes_financieres                 = $aBalances[$this->id_bilan]['DS'] + $aBalances[$this->id_bilan]['DT'] + $aBalances[$this->id_bilan]['DU'] + $aBalances[$this->id_bilan]['DV'];
        $this->dettes_fournisseurs                = $aBalances[$this->id_bilan]['DW'] + $aBalances[$this->id_bilan]['DX'];
        $this->autres_dettes                      = $aBalances[$this->id_bilan]['DY'] + $aBalances[$this->id_bilan]['DZ'] + $aBalances[$this->id_bilan]['EA'];
        $this->comptes_regularisation_passif      = $aBalances[$this->id_bilan]['EB'] + $aBalances[$this->id_bilan]['ED'];
        $this->update();
    }
}
