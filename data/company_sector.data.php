<?php

class company_sector extends company_sector_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::company_sector($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `company_sector`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `company_sector` ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_company_sector')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `company_sector` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }
}
