<?php

class companies_balance_type extends companies_balance_type_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::companies_balance_type($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM companies_balance_type' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM companies_balance_type ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_balance_type')
    {
        return $this->bdd->fetch_array($this->bdd->query('SELECT * FROM companies_balance_type WHERE ' . $field . ' = "' . $id . '"'), 0, 0) > 0;
    }

    public function getAllByCode()
    {
        $aCodes  = array();
        $rResult = $this->bdd->query('SELECT * FROM companies_balance_type ORDER BY code ASC');

        while ($aResult = $this->bdd->fetch_assoc($rResult)) {
            $aCodes[$aResult['code']] = $aResult;
        }

        return $aCodes;
    }

    public function getAllByType()
    {
        $aTypes  = array();
        $rResult = $this->bdd->query('SELECT * FROM companies_balance_type ORDER BY code ASC');

        while ($aResult = $this->bdd->fetch_assoc($rResult)) {
            $aTypes[$aResult['id_balance_type']] = $aResult;
        }

        return $aTypes;
    }
}
