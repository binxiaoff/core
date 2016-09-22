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

class indexage_vos_operations extends indexage_vos_operations_crud
{

    function indexage_vos_operations($bdd, $params = '')
    {
        parent::indexage_vos_operations($bdd, $params);
    }

    function get($id, $field = 'id')
    {
        return parent::get($id, $field);
    }

    function update($cs = '')
    {
        parent::update($cs);
    }

    function delete($id, $field = 'id')
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
        $sql = 'SELECT * FROM `indexage_vos_operations`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    function getLastOperationDate($iIdClient)
    {
        $sSql = 'SELECT MAX(date_operation) as last_operation_date FROM `indexage_vos_operations` WHERE id_client = ' . $iIdClient;

        $rResult = $this->bdd->query($sSql);
        return ($this->bdd->result($rResult, 0, 0));
    }

    function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT count(*) FROM `indexage_vos_operations` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    function exist($id, $field = 'id')
    {
        $sql    = 'SELECT * FROM `indexage_vos_operations` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    function get_liste_libelle_projet($where)
    {
        if ($where != '') {
            $where = ' AND ' . $where;
        }

        $sql = 'SELECT DISTINCT(libelle_projet) as title, id_projet as id_project
				FROM `indexage_vos_operations`
				WHERE 1 = 1
				' . $where . '
				GROUP BY id_projet
		';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    /**
     * @param $clientId
     * @param $annee
     * @return string
     */
    function getFiscalBalanceToDeclare($clientId, $annee)
    {
        $sql = "SELECT solde 
                FROM indexage_vos_operations 
                WHERE id_client = " . $clientId . " AND date_operation < '$annee-01-01 00:00:00' ORDER BY date_operation DESC LIMIT 0,1";

        $balance = $this->bdd->executeQuery($sql)->fetchColumn(0);

        $sql = "SELECT sum(amount)
                FROM bids
                INNER JOIN `lenders_accounts` ON lenders_accounts.id_lender_account = bids.id_lender_account
                WHERE lenders_accounts.id_client_owner = " . $clientId . " AND bids.added < '$annee-01-01 00:00:00' AND bids.updated >= '$annee-01-01 00:00:00'";
        $bidSum = $this->bdd->executeQuery($sql)->fetchColumn(0);
        return bcdiv(bcadd($balance, $bidSum, 2), 100, 2);
    }

    public function getLenderOperations(array $transactionType, $clientId, $startDate, $endDate, $projectId = null, $orderBy = 'date_operation DESC, id_transaction DESC')
    {
        $bind     = [
            'transactionType' => $transactionType,
            'clientId'        => $clientId,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'orderBy'         => $orderBy
        ];
        $bindType = [
            'clientId'  => \PDO::PARAM_INT,
            'startDate' => \PDO::PARAM_STR,
            'endDate'   => \PDO::PARAM_STR,
            'orderBy'   => \PDO::PARAM_STR
        ];
        $sql      = 'SELECT
                    id,
                    id_client,
                    id_transaction,
                    id_echeancier,
                    id_projet,
                    type_transaction,
                    libelle_operation,
                    CASE
                        WHEN bdc = 0 THEN
                          NULL 
                        ELSE
                          bdc
                    END AS bdc,
                    libelle_projet,
                    date_operation,
                    ROUND(solde / 100, 2) AS solde,
                    ROUND(montant_operation / 100, 2) AS montant_operation,
                    ROUND(montant_capital / 100, 2) AS montant_capital,
                    ROUND(montant_interet / 100, 2) AS montant_interet,
                    libelle_prelevement,
                    ROUND(montant_prelevement / 100, 2) * -1 AS montant_prelevement,
                    updated,
                    added
                FROM indexage_vos_operations
                WHERE id_client = :clientId
                  AND DATE(date_operation) >= :startDate
                  AND DATE(date_operation) <= :endDate
                ';
        if (false === empty($transactionType)) {
            $sql .= ' AND type_transaction in (:transactionType)';
            $bind['transactionType']     = $transactionType;
            $bindType['transactionType'] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
        }
        if (false == empty($projectId)) {
            $sql .= ' AND id_projet = :projectId';
            $bind['projectId']     = $projectId;
            $bindType['projectId'] = \PDO::PARAM_INT;
        }
        $sql .= ' ORDER BY :orderBy';
        /** @var \Doctrine\DBAL\Statement $statement */
        $statement = $this->bdd->executeQuery($sql, $bind, $bindType);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
