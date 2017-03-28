<?php

class project_abandon_reason extends project_abandon_reason_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::project_abandon_reason($bdd, $params);
    }

    public function select($where = '', $order = '', $offset = '', $limit = '')
    {
        $query = 'SELECT * FROM project_abandon_reason' .
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
}
