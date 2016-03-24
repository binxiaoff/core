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

class clients_status_history extends clients_status_history_crud
{

    public function __construct($bdd, $params = '')
    {
        parent::clients_status_history($bdd, $params);
    }

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

    public function get_last_statut($id_client)
    {
        $sql = 'SELECT * FROM `clients_status_history` WHERE id_client = ' . $id_client . ' ORDER BY id_client_status_history DESC LIMIT 1';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }

        return $result[0];
    }

    /**
     * @param clients $oClient
     * @return string
     */
    public function getCompletnessRequestContent(\clients $oClient)
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