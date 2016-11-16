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

class users_history extends users_history_crud
{
    const FORM_ID_LENDER = 3;
    const FORM_NAME_TAX_EXEMPTION = 'modification exoneration fiscale';

    function users_history($bdd, $params = '')
    {
        parent::users_history($bdd, $params);
    }

    function get($id, $field = 'id_user_history')
    {
        return parent::get($id, $field);
    }

    function update($cs = '')
    {
        parent::update($cs);
    }

    function delete($id, $field = 'id_user_history')
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
        $sql = 'SELECT * FROM `users_history`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `users_history` ' . $where;

        $result = $this->bdd->query($sql);
        return (int) ($this->bdd->result($result, 0, 0));
    }

    function exist($id, $field = 'id_user_history')
    {
        $sql    = 'SELECT * FROM `users_history` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result) > 0);
    }

    function histo($id_form, $nom_form, $id_user, $serialize)
    {
        $this->id_form   = $id_form;
        $this->nom_form  = $nom_form;
        $this->id_user   = $id_user;
        $this->serialize = $serialize;
        $this->create();
    }

    /**
     * @return array
     */
    public function getTaxExemptionHistoryAction()
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->bdd->createQueryBuilder();

        $queryBuilder->select('*')
            ->from('users_history')
            ->where('id_form = :id_form')
            ->andWhere('nom_form = :form_name')
            ->setParameter('id_form', self::FORM_ID_LENDER, \PDO::PARAM_INT)
            ->setParameter('form_name', self::FORM_NAME_TAX_EXEMPTION, \PDO::PARAM_STR)
            ->orderBy('added', 'DESC');

        /** @var \Doctrine\DBAL\Driver\Statement $statement */
        $statement = $queryBuilder->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
