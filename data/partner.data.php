<?php

class partner extends partner_crud
{
    const PARTNER_U_CAR_LABEL    = 'u_car';
    const PARTNER_UNILEND_LABEL  = 'unilend';
    const PARTNER_MEDILEND_LABEL = 'medilend';

    public function __construct($bdd, $params = '')
    {
        parent::partner($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `partner`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `partner`' . $where));
    }

    public function exist($id, $field = 'id_partner')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `partner` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }
}
