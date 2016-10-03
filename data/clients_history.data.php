<?php
// **************************************************************************************************** //
// ***************************************    ASPARTAM    ********************************************* //
// **************************************************************************************************** //
//
// Copyright (c) 2008-2011, equinoa
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
// associated documentation files (the "Software"), to deal in the Software without restriction,
// including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
// subject to the following conditions:
// The above copyright notice and this permission notice shall be included in all copies
// or substantial portions of the Software.
// The Software is provided "as is", without warranty of any kind, express or implied, including but
// not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.
// In no event shall the authors or copyright holders equinoa be liable for any claim,
// damages or other liability, whether in an action of contract, tort or otherwise, arising from,
// out of or in connection with the software or the use or other dealings in the Software.
// Except as contained in this notice, the name of equinoa shall not be used in advertising
// or otherwise to promote the sale, use or other dealings in this Software without
// prior written authorization from equinoa.
//
//  Version : 2.4.0
//  Date : 21/03/2011
//  Coupable : CM
//
// **************************************************************************************************** //

class clients_history extends clients_history_crud
{
    const STATUS_ACTION_LOGIN              = 1;
    const STATUS_ACTION_ACCOUNT_CREATION   = 2;
    const STATUS_ACTION_DOSSIER_SUBMISSION = 3;
    const TYPE_CLIENT_LENDER               = 1;
    const TYPE_CLIENT_BORROWER             = 2;
    const TYPE_CLIENT_LENDER_BORROWER      = 3;

    function clients_history($bdd, $params = '')
    {
        parent::clients_history($bdd, $params);
    }

    function get($id, $field = 'id_history')
    {
        return parent::get($id, $field);
    }

    function update($cs = '')
    {
        parent::update($cs);
    }

    function delete($id, $field = 'id_history')
    {
        parent::delete($id, $field);
    }

    function create($cs = '')
    {
        $id = parent::create($cs);
        return $id;
    }

    function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `clients_history`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT count(*) FROM `clients_history` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    function exist($id, $field = 'id_history')
    {
        $sql    = 'SELECT * FROM `clients_history` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    function getNb($month, $year, $where = '', $distinct = '')
    {
        if ($where != '') {
            $where = ' AND ' . $where;
        }
        if ($distinct != '') {
            $distinct = 'DISTINCT(id_client)';
        } else {
            $distinct = 'id_client';
        }
        $sql = 'SELECT COUNT(' . $distinct . ') FROM `clients_history` WHERE MONTH(added) = ' . $month . ' AND YEAR(added) = ' . $year . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    /**
     * @param \clients $client
     * @param $actionStatus
     * @return int;
     */
    public function logClientAction(\clients $client, $actionStatus)
    {
        $isLender        = $client->isLender();
        $isBorrower      = $client->isBorrower();
        $this->id_client = $client->id_client;
        $this->status    = $actionStatus;

        if ($isLender && $isBorrower) {
            $this->type = self::TYPE_CLIENT_LENDER_BORROWER;
        } elseif ($isLender) {
            $this->type = self::TYPE_CLIENT_LENDER;
        } elseif ($isBorrower) {
            $this->type = self::TYPE_CLIENT_BORROWER;
        }
        return $this->create();
    }

    /**
     * @param int $clientId
     * @return mixed
     */
    public function getClientLastLogin($clientId)
    {
        $sql = '
        SELECT MAX(added) AS last_login_date FROM clients_history cs
        WHERE cs.id_client = :id_client
        AND cs.status = :status
        ';
        /** @var \Doctrine\DBAL\Statement $query */
        $query = $this->bdd->executeQuery($sql, ['id_client' => $clientId, 'status' => self::STATUS_ACTION_LOGIN], ['id_client' => \PDO::PARAM_INT, 'status' => \PDO::PARAM_INT]);

        return $query->fetchColumn();
    }
}
