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

use \Doctrine\DBAL\Driver\Statement;

class ws_external_resource extends ws_external_resource_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::ws_external_resource($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `ws_external_resource`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `ws_external_resource`' . $where));
    }

    public function exist($id, $field = 'id_resource')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `ws_external_resource` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }

    /**
     * @param $providerName
     * @param $resourceName
     * @param $method
     * @return int
     */
    public function getResource($providerName, $resourceName, $method)
    {
        $sql = '
            SELECT id_resource
            FROM ws_external_resource
            WHERE provider_name = :provider AND resource_name = :resource AND method = :method
        ';
        /** @var Statement $statement */
        $statement = $this->bdd->executeQuery(
            $sql,
            ['provider' => $providerName, 'resource' => $resourceName, 'method' => $method],
            ['provider' => \PDO::PARAM_STR, 'resource' => \PDO::PARAM_STR, 'method' => \PDO::PARAM_STR]
        );

        return $statement->fetchColumn(0);
    }
}
