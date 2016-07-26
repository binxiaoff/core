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
class project_rate_settings extends project_rate_settings_crud
{
    const STATUS_ACTIVE   = 1;
    const STATUS_ARCHIVED = 2;

    public function __construct($bdd, $params = '')
    {
        parent::project_rate_settings($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `project_rate_settings`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        return (int) $this->bdd->result($this->bdd->query('SELECT COUNT(*) FROM `project_rate_settings` ' . $where), 0, 0);
    }

    public function exist($id, $field = 'id_rate')
    {
        return $this->bdd->fetch_assoc($this->bdd->query('SELECT * FROM `project_rate_settings` WHERE ' . $field . ' = "' . $id . '"')) > 0;
    }

    public function getSettings($evaluation = null, $periodId = null, $status = array(self::STATUS_ACTIVE), $order = ['pp.min' => 'ASC', 'prs.evaluation' => 'DESC'], $limit = null, $offset = null)
    {
        $queryBuilder = $this->bdd->createQueryBuilder();

        $queryBuilder
            ->select('*')
            ->from('project_rate_settings','prs')
            ->innerJoin('prs', 'project_period', 'pp', 'pp.id_period = prs.id_period and pp.status = :pp_status')
            ->setParameter('pp_status', project_period::STATUS_ACTIVE);

        if ($evaluation !== null) {
            $queryBuilder->andWhere('prs.evaluation = :evaluation');
            $queryBuilder->setParameter('evaluation', $evaluation);
        }
        if ($periodId !== null) {
            $queryBuilder->andWhere('prs.id_period = :id_period');
            $queryBuilder->setParameter('id_period', $periodId);
        }
        if (is_array($status) && false === empty($status)) {
            $queryBuilder->andWhere('prs.status in (:status)');
            $queryBuilder->setParameter('status', $status, \Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
        }

        if (is_array($order) && false === empty($order)) {
            foreach ($order as $sort => $oder) {
                $queryBuilder->addOrderBy($sort, $oder);
            }
        }

        if (is_numeric($limit)) {
            $queryBuilder->setMaxResults($limit);
        }
        if (is_numeric($offset)) {
            $queryBuilder->setFirstResult($offset);
        }
        $statement = $queryBuilder->execute();
        return $statement->fetchAll();
    }
}
