<?php

class projects_comments extends projects_comments_crud
{
    public function projects_comments($bdd, $params = '')
    {
        parent::projects_comments($bdd, $params);
    }

    public function select($where = '', $order = '', $offset = '', $limit = '')
    {
        $query = 'SELECT * FROM projects_comments' .
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
