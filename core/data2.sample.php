<?php

class --classe-- extends --classe--_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::--table--($bdd, $params);
    }

    public function select($where = '', $order = '', $offset = '', $limit = '')
    {
        $query = 'SELECT * FROM --table--' .
            (empty($where) ? '' : ' WHERE ' . $where) .
            (empty($order) ? '' : ' ORDER BY ' . $order) .
            (empty($limit) ? '' : ' LIMIT ' . $limit) .
            (empty($offset) ? '' : ' OFFSET ' . $offset);

        $result    = [];
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

        $query     = 'SELECT COUNT(*) FROM --table-- ' . $where;
        $statement = $this->bdd->query($query);
        return (int) $this->bdd->result($statement);
    }

    public function exist($fields)
    {
        $list = '';
        foreach ($fields as $field => $value) {
            $list .= ' AND ' . $field . ' = "' . $value . '" ';
        }

        $query     = 'SELECT * FROM --table-- WHERE 1 = 1' . $list;
        $statement = $this->bdd->query($query);
        return $this->bdd->fetch_assoc($statement) > 0;
    }
}
