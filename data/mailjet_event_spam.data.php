<?php

class mailjet_event_spam extends mailjet_event_spam_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::mailjet_event_spam($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `mailjet_event_spam`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `mailjet_event_spam`' . $where));
    }

    public function exist($id, $field = 'id')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `mailjet_event_spam` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }
}
