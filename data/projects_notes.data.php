<?php

class projects_notes extends projects_notes_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::projects_notes($bdd, $params);
    }

    public function select($where = '', $order = '', $offset = '', $limit = '')
    {
        $query = 'SELECT * FROM projects_notes' .
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

        $query     = 'SELECT COUNT(*) FROM projects_notes ' . $where;
        $statement = $this->bdd->query($query);
        return (int) $this->bdd->result($statement);
    }

    public function exist($id, $field = 'id_project_notes')
    {
        $query     = 'SELECT * FROM projects_notes WHERE ' . $field . ' = "' . $id . '"';
        $statement = $this->bdd->query($query);
        return $this->bdd->fetch_assoc($statement) > 0;
    }
}
