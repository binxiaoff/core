<?php

class company_balance extends company_balance_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::company_balance($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM company_balance' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM company_balance ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_balance')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM company_balance WHERE ' . $field . ' = "' . $id . '"'), 0, 0) > 0;
    }

    public function getBalanceSheetsByAnnualAccount(array $aAnnualAccountsIds)
    {
        $aAnnualAccounts    = array();
        $oBalanceTypes      = new \company_balance_type($this->bdd);
        $aBalanceTypes      = $oBalanceTypes->getAllByType();
        $aBalanceTypes      = array_column($aBalanceTypes, 'code', 'id_balance_type');
        $sAnnualAccountsIds = implode(', ', $aAnnualAccountsIds);

        foreach ($aAnnualAccountsIds as $iAnnualAccountsId) {
            $aAnnualAccounts[$iAnnualAccountsId] = array_fill_keys($aBalanceTypes, 0);
        }

        foreach ($this->select('id_bilan IN (' . $sAnnualAccountsIds . ')', 'FIELD(id_bilan, ' . $sAnnualAccountsIds . ') ASC') as $aAnnualAccount) {
            $aAnnualAccounts[$aAnnualAccount['id_bilan']][$aBalanceTypes[$aAnnualAccount['id_balance_type']]] = $aAnnualAccount['value'];
        }

        return $aAnnualAccounts;
    }
}
