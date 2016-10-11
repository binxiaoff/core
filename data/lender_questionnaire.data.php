<?php

class lender_questionnaire extends lender_questionnaire_crud
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE   = 1;

    public function __construct($bdd, $params = '')
    {
        parent::lender_questionnaire($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `lender_questionnaire`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `lender_questionnaire`' . $where));
    }

    public function exist($id, $field = 'id_lender_questionnaire')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `lender_questionnaire` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }
}
