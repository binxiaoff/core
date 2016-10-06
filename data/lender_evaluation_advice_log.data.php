<?php

class lender_evaluation_advice_log extends lender_evaluation_advice_log_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::lender_evaluation_advice_log($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `lender_evaluation_advice_log`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `lender_evaluation_advice_log`' . $where));
    }

    public function exist($id, $field = 'id_lender_evaluation_log')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `lender_evaluation_advice_log` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }
}
