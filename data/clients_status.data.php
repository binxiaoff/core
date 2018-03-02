<?php

class clients_status extends clients_status_crud
{
    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `clients_status`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }

        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $result = $this->bdd->query('SELECT COUNT(*) FROM `clients_status` ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_client_status')
    {
        $result = $this->bdd->query('SELECT * FROM `clients_status` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function getLastStatut($id_client)
    {
        $sql = '
            SELECT id_client_status
            FROM clients_status_history
            WHERE id_client = ' . $id_client . '
            ORDER BY added DESC, id_client_status_history DESC
            LIMIT 1';

        $result           = $this->bdd->query($sql);
        $id_client_status = (int) $this->bdd->result($result, 0, 0);

        return parent::get($id_client_status, 'id_client_status');
    }
}
