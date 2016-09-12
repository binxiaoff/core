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
class product_underlying_contract extends product_underlying_contract_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::product_underlying_contract($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $result   = array();
        $resultat = $this->bdd->query('SELECT * FROM product_underlying_contract' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : '')));
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

        $result = $this->bdd->query('SELECT COUNT(*) FROM product_underlying_contract ' . $where);
        return (int) $this->bdd->result($result, 0, 0);
    }

    public function exist($list_field_value)
    {
        $list = '';
        foreach ($list_field_value as $champ => $valeur) {
            $list .= ' AND ' . $champ . ' = "' . $valeur . '" ';
        }

        $result = $this->bdd->query('SELECT * FROM product_underlying_contract WHERE 1 = 1 ' . $list);
        return ($this->bdd->fetch_assoc($result) > 0);
    }

    public function getUnderlyingContractsByProduct($productId = null)
    {
        if (null === $productId) {
            $productId = $this->id_product;
        }

        $queryBuilder = $this->bdd->createQueryBuilder();

        $queryBuilder->select('uc.*')
            ->from('product_underlying_contract', 'puc')
            ->innerJoin('puc', 'underlying_contract', 'uc', 'puc.id_contract = uc.id_contract')
            ->where('puc.id_product = :id_product')
            ->setParameter('id_product', $productId);

        return $queryBuilder->execute()->fetchAll();
    }

    public function getContractAttrByProduct($productId = null)
    {
        if (null === $productId) {
            $productId = $this->id_product;
        }

        $queryBuilder = $this->bdd->createQueryBuilder();

        $queryBuilder->select('uc.*')
                     ->from('product_underlying_contract', 'puc')
                     ->innerJoin('puc', 'underlying_contract', 'uc', 'puc.id_contract = uc.id_contract')
                     ->where('puc.id_product = :id_product')
                     ->setParameter('id_product', $productId);

        return $queryBuilder->execute()->fetchAll();
    }
}
