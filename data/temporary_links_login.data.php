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
        $result   = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function generateTemporaryLink($iClientId)
    {
        $sToken          = md5($iClientId).md5(time());
        $oDateTime       =  new \datetime('NOW + 1 week');
        $sExpiryDateTime = $oDateTime->format('Y-m-d H:i:s');

        $this->id_client = $iClientId;
        $this->token     = $sToken;
        $this->expires   = $sExpiryDateTime;
        $this->create();

        return $sToken;
    }
}
