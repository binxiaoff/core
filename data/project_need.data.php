<?php

class project_need extends project_need_crud
{
    /**
     * @todo we should prefer using labels but we don't have some for the moment
     */
    const PARENT_TYPE_TRANSACTION = 36;

    public function __construct($database, $parameters = '')
    {
        parent::project_need($database, $parameters);
    }

    public function select($where = null, $order = null)
    {
        $query = 'SELECT * FROM project_need';

        if (null !== $where) {
            $query .= ' WHERE ' . $where;
        }

        if (null !== $order) {
            $query .= ' ORDER BY ' . $order;
        }

        $result    = [];
        $statement = $this->bdd->query($query);
        while ($record = $this->bdd->fetch_assoc($statement)) {
            $result[] = $record;
        }
        return $result;
    }

    /**
     * Retrieve 2 levels tree of available project needs
     *
     * @return array
     */
    public function getTree()
    {
        $tree = [];

        foreach ($this->select(null, 'id_parent ASC, rank ASC') as $need) {
            if (0 == $need['id_parent']) {
                $tree[$need['id_project_need']]             = $need;
                $tree[$need['id_project_need']]['children'] = [];
            } else {
                $tree[$need['id_parent']]['children'][] = $need;
            }
        }

        return $tree;
    }
}
