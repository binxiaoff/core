<?php

class --classe-- extends --classe--_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::--table--($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $result    = [];
        $query     = 'SELECT * FROM `--table--`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));
        $statement = $this->bdd->query($query);
        while ($record = $this->bdd->fetch_assoc($statement)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `--table--`' . $where));
    }

    public function exist($id, $field = '--id--')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `--table--` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }
}
