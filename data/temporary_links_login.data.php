<?php

class temporary_links_login extends temporary_links_login_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::temporary_links_login($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $resultat = $this->bdd->query('SELECT * FROM `temporary_links_login`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : '')));
        $result   = [];
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function generateTemporaryLink($clientId)
    {
        $token          = md5($clientId) . md5(time());
        $expiryDateTime = new \DateTime('NOW + 1 week');

        $this->id_client = $clientId;
        $this->token     = $token;
        $this->expires   = $expiryDateTime->format('Y-m-d H:i:s');
        $this->create();

        return $token;
    }
}
