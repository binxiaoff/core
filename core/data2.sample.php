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
        $query     = 'SELECT * FROM --table--' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));
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

        $result = $this->bdd->query('SELECT COUNT(*) FROM --table-- ' . $where);
        return (int) $this->bdd->result($result);
    }

    public function exist($fields)
    {
        $list = '';
        foreach ($fields as $field => $value) {
            $list .= ' AND ' . $field . ' = "' . $value . '" ';
        }

        $result = $this->bdd->query('SELECT * FROM --table-- WHERE 1 = 1' . $list);
        return ($this->bdd->fetch_assoc($result) > 0);
    }
}
