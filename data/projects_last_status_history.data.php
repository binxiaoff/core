<?php

class projects_last_status_history
{
    /**
     * @var bdd
     */
    private $bdd;

    /**
     * @var int
     */
    public $id_project_status_history;

    /**
     * @var int
     */
    public $id_project;

    public function __construct(\bdd $bdd, $params = '')
    {
        $this->bdd = $bdd;
    }

    public function get($id, $field = 'id_project')
    {
        $result = $this->bdd->query('SELECT * FROM  projects_last_status_history WHERE ' . $field . ' = "' . $id . '"');

        if ($this->bdd->num_rows($result) == 1) {
            $record = $this->bdd->fetch_assoc($result);

            $this->id_project_status_history = $record['id_project_status_history'];
            $this->id_project                = $record['id_project'];

            return true;
        }

        return false;
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM projects_last_status_history' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
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

        $result = $this->bdd->query('SELECT count(*) FROM projects_last_status_history ' . $where);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_project')
    {
        $result = $this->bdd->query('SELECT * FROM projects_last_status_history WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_assoc($result, 0, 0) > 0);
    }
}
