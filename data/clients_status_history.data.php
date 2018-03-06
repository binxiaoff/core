<?php

class clients_status_history extends clients_status_history_crud
{
    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql      = 'SELECT * FROM `clients_status_history`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));
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

        $sql = 'SELECT count(*) FROM `clients_status_history` ' . $where;

        $result = $this->bdd->query($sql);

        return (int) ($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_client_status_history')
    {
        $sql    = 'SELECT * FROM `clients_status_history` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);

        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    public function addStatus($id_user, $status, $id_client, $content = '', $numerorelance = false)
    {
        $sql = 'SELECT id_client_status FROM `clients_status` WHERE status = ' . $status . ' ';

        $result           = $this->bdd->query($sql);
        $id_client_status = (int) ($this->bdd->result($result, 0, 0));

        $this->id_client        = $id_client;
        $this->id_client_status = $id_client_status;
        $this->id_user          = $id_user;
        $this->content          = $content;
        if (is_integer($numerorelance)) {
            $this->numero_relance = $numerorelance;
        }
        $this->id_client_status_history = $this->create();

        return $this->id_client_status_history;
    }

    /**
     * @param clients $oClient
     *
     * @return string
     */
    public function getCompletenessRequestContent(\clients $oClient)
    {
        $sQuery = ' SELECT content FROM `clients_status_history` where id_client = ' . $oClient->id_client . ' and id_client_status = 2 order by added desc limit 1 ';
        $rQuery = $this->bdd->query($sQuery);
        $sCompletenessRequestContent = "";

        while ($aRow = $this->bdd->fetch_array($rQuery)) {
            $sCompletenessRequestContent = $aRow['content'];
        }

        return $sCompletenessRequestContent;
    }
}
