<?php


class transfer_type extends transfer_type_crud
{
    const TYPE_INHERITANCE = 1;

    public function __construct($bdd, $params = '')
    {
        parent::__construct($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `transfer_type`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `transfer_type` ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_type')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `transfer_type` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }
}
