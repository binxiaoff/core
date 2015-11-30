<?php

class companies_balance extends companies_balance_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::companies_balance($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM companies_balance' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM companies_balance ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_balance')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM companies_balance WHERE ' . $field . ' = "' . $id . '"'), 0, 0) > 0;
    }

    public function getBalanceSheetsByAnnualAccount($aAnnualAccountsIds)
    {
        $aAnnualAccounts    = array();
        $oBalanceTypes      = new \companies_balance_type($this->bdd);
        $aBalanceTypes      = $oBalanceTypes->getAllByType();
        $sAnnualAccountsIds = implode(', ', $aAnnualAccountsIds);

        foreach ($aAnnualAccountsIds as $iAnnualAccountsId) {
            $aAnnualAccounts[$iAnnualAccountsId] = array_fill_keys(array_keys($aBalanceTypes), 0);
        }

        foreach ($this->select('id_bilan IN (' . $sAnnualAccountsIds . ')', 'FIELD(id_bilan, ' . $sAnnualAccountsIds . ') ASC') as $aAnnualAccount) {
            $aAnnualAccounts[$aAnnualAccount['id_bilan']][$aAnnualAccount['id_balance_type']] = $aAnnualAccount['value'];
        }

        return $aAnnualAccounts;
    }
}
