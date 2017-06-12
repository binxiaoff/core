<?php

class project_rejection_reason extends project_rejection_reason_crud
{
    const SUSPENSIVE_CONDITIONS = 17;

    public function __construct($bdd, $params = '')
    {
        parent::project_rejection_reason($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM project_rejection_reason' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM project_rejection_reason' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_rejection')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM project_rejection_reason WHERE ' . $field . ' = "' . $id . '"'), 0, 0) > 0;
    }
}
