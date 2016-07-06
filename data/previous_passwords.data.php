<?php

class previous_passwords extends previous_passwords_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::previous_passwords($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `previous_passwords`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `previous_passwords` ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `previous_passwords` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }

    public function isValidPassword($sPassword, $sUserId)
    {
        $aPreviousPasswords = $this->select($sUserId, 'id_user');
        foreach ($aPreviousPasswords as $previousPassword) {
            if (password_verify($sPassword, $previousPassword['password']) || $sPassword == md5($previousPassword['password'])) {
                return false;
            }
        }
        return true;
    }

    public function deleteOldPasswords($sUserId)
    {
        $this->bdd->query('
            DELETE FROM previous_passwords
            WHERE id IN (
              SELECT id FROM (
                SELECT id FROM previous_passwords
                WHERE id_user = ' . $sUserId . '
                ORDER BY archived DESC
                LIMIT 1 OFFSET 12
              ) a
            )');
    }
}
