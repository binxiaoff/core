<?php


class loan_transfer extends loan_transfer_crud
{

    public function __construct($bdd, $params = '')
    {
        parent::loan_transfer($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `loan_transfer`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `loan_transfer` ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_product')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `loan_transfer` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }
}
