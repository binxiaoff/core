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

class clients_status extends clients_status_crud
{
    const TO_BE_CHECKED         = 10;
    const COMPLETENESS          = 20;
    const COMPLETENESS_REMINDER = 30;
    const COMPLETENESS_REPLY    = 40;
    const MODIFICATION          = 50;
    const VALIDATED             = 60;
    const CLOSED_LENDER_REQUEST = 70;
    const CLOSED_BY_UNILEND     = 80;
    const CLOSED_DEFINITELY     = 100;

    public function __construct($bdd,$params='')
    {
        parent::clients_status($bdd, $params);
    }

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

    public function listCompletudes()
    {
        $sql = '
        SELECT
            MAX(csh.added) as added,
            csh.id_client,
            (SELECT h.id_client_status FROM clients_status_history h WHERE h.id_client = csh.id_client ORDER BY added DESC LIMIT 1) as id_client_status,
            (SELECT h.content FROM clients_status_history h WHERE h.id_client = csh.id_client ORDER BY added DESC LIMIT 1) as content
        FROM clients_status_history csh
        GROUP BY csh.id_client';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            // On recup que les clients en completude
            if ($record['id_client_status'] == self::COMPLETENESS) {
                $result[] = $record;
            }
        }

        return $result;
    }
}
