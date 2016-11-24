<?php

class company_balance_type extends company_balance_type_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::company_balance_type($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM company_balance_type' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM company_balance_type ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_balance_type')
    {
        return $this->bdd->fetch_array($this->bdd->query('SELECT * FROM company_balance_type WHERE ' . $field . ' = "' . $id . '"'), 0, 0) > 0;
    }

    public function getAllByCode($taxFormTypeId)
    {
        $aCodes  = array();

        $query = 'SELECT * FROM company_balance_type WHERE id_company_tax_form_type = :id_tax_form_type ORDER BY code ASC';
        $statement = $this->bdd->executeQuery($query, ['id_tax_form_type' => $taxFormTypeId]);

        while ($aResult = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $aCodes[$aResult['code']] = $aResult;
        }

        return $aCodes;
    }

    public function getAllByType($taxFormTypeId)
    {
        $aTypes  = array();
        $query  = 'SELECT * FROM company_balance_type WHERE id_company_tax_form_type = :id_tax_form_type ORDER BY code ASC';
        $statement = $this->bdd->executeQuery($query, ['id_tax_form_type' => $taxFormTypeId]);

        while ($aResult = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $aTypes[$aResult['id_balance_type']] = $aResult;
        }

        return $aTypes;
    }
}
