<?php

class project_rate_settings extends project_rate_settings_crud
{
    public function __construct($bdd, $params = '')
    {
        parent::__construct($bdd, $params);
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

    /**
     * @param null  $evaluation
     * @param null  $periodId
     * @param array $status
     * @param array $order
     * @param null  $limit
     * @param null  $offset
     *
     * @return array
     */
    public function getSettings(
        $evaluation = null,
        $periodId = null,
        $status = [\Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRateSettings::STATUS_ACTIVE],
        $order = ['pp.min' => 'ASC', 'prs.evaluation' => 'DESC'],
        $limit = null,
        $offset = null
    ) {
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
