<?php

class xerfi extends xerfi_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::xerfi($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM xerfi' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $result   = array();
        $resultat = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function exist($id, $field = 'naf')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM xerfi WHERE ' . $field . ' = "' . $id . '"'), 0, 0) > 0;
    }
}
