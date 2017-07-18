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

use Unilend\core\Loader;

class autobid extends autobid_crud
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE   = 1;
    const STATUS_ARCHIVED = 2;

    const THRESHOLD_AUTO_BID_BALANCE_LOW = 3;

    public function __construct($bdd, $params = '')
    {
        parent::autobid($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }

        $sql = 'SELECT * FROM `autobid`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `autobid` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_autobid')
    {
        $sql    = 'SELECT * FROM `autobid` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    public function getValidationDate($iLenderId)
    {
        $rResult = $this->bdd->query('SELECT MAX(`updated`) FROM `autobid` WHERE id_lender = ' . $iLenderId . ' AND status != ' . self::STATUS_ARCHIVED);
        return $this->bdd->result($rResult, 0, 0);
    }

    /**
     * @param string $evaluation
     * @param int    $duration
     *
     * @return mixed
     */
    public function sumAmount($evaluation, $duration)
    {
        $query = '
            SELECT SUM(amount)
            FROM autobid a
              INNER JOIN project_period pp ON pp.id_period = a.id_period
              INNER JOIN project_rate_settings prs ON pp.id_period = prs.id_period
              INNER JOIN wallet w ON a.id_lender = w.id
            WHERE ' . $duration . ' BETWEEN pp.min AND pp.max
                AND a.status = ' . self::STATUS_ACTIVE . '
                AND pp.status = ' . \project_period::STATUS_ACTIVE . '
                AND prs.status = ' . project_rate_settings::STATUS_ACTIVE . '
                AND a.evaluation = "' . $evaluation . '"
                AND prs.evaluation = "' . $evaluation . '"
                AND a.rate_min <= prs.rate_max
                AND w.available_balance >= a.amount';

        $result = $this->bdd->query($query);
        return $this->bdd->result($result);
    }

    public function getSettings($lenderId = null, $evaluation = null, $periodId = null, $status = array(self::STATUS_ACTIVE), $order = ['pp.min' => 'ASC', 'a.evaluation' => 'DESC'], $limit = null, $offset = null)
    {
        $queryBuilder = $this->bdd->createQueryBuilder();

        $queryBuilder
            ->select('a.*, pp.id_period as id_period, pp.min as period_min, pp.max as period_max, pp.status as period_status, w.id_client AS id_client')
            ->from('autobid','a')
            ->innerJoin('a', 'project_period', 'pp', 'pp.id_period = a.id_period and pp.status = :pp_status')
            ->innerJoin('a', 'wallet', 'w', 'w.id = a.id_lender')
            ->setParameter('pp_status', project_period::STATUS_ACTIVE);

        if ($lenderId !== null) {
            $queryBuilder->andWhere('a.id_lender = :id_lender');
            $queryBuilder->setParameter('id_lender', $lenderId);
        }
        if ($evaluation !== null) {
            $queryBuilder->andWhere('a.evaluation = :evaluation');
            $queryBuilder->setParameter('evaluation', $evaluation);
        }
        if ($periodId !== null) {
            $queryBuilder->andWhere('a.id_period = :id_period');
            $queryBuilder->setParameter('id_period', $periodId);
        }
        if (is_array($status) && false === empty($status)) {
            $queryBuilder->andWhere('a.status in (:status)');
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