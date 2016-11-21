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
}
