<?php

class clients_gestion_type_notif extends clients_gestion_type_notif_crud
{
    const TYPE_NEW_PROJECT                   = 1;
    const TYPE_BID_PLACED                    = 2;
    const TYPE_BID_REJECTED                  = 3;
    const TYPE_LOAN_ACCEPTED                 = 4;
    const TYPE_REPAYMENT                     = 5;
    const TYPE_BANK_TRANSFER_CREDIT          = 6;
    const TYPE_CREDIT_CARD_CREDIT            = 7;
    const TYPE_DEBIT                         = 8;
    const TYPE_PROJECT_PROBLEM               = 9;
    const TYPE_AUTOBID_BALANCE_LOW           = 10;
    const TYPE_AUTOBID_ACCEPTED_REJECTED_BID = 13;

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `clients_gestion_type_notif`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $result = $this->bdd->query('SELECT COUNT(*) FROM `clients_gestion_type_notif` ' . $where);
        return (int)$this->bdd->result($result, 0, 0);
    }

    public function exist($id, $field = 'id_client_gestion_type_notif')
    {
        $result = $this->bdd->query('SELECT * FROM `clients_gestion_type_notif` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }
}
