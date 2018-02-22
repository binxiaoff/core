<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Autobid;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectPeriod;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class AutobidRepository extends EntityRepository
{
    /**
     * @param string $evaluation
     * @param int    $duration
     *
     * @return int
     * @throws ORMException
     */
    public function getSumAmount(string $evaluation, int $duration)
    {
        $queryBuilder = $this->createQueryBuilder('a');
        $queryBuilder->select('IFNULL(SUM(a.amount), 0)')
            ->innerJoin('UnilendCoreBusinessBundle:ProjectPeriod', 'pp', Join::WITH, 'a.idPeriod = pp.idPeriod')
            ->innerJoin('UnilendCoreBusinessBundle:ProjectRateSettings', 'prs', Join::WITH, 'pp.idPeriod = prs.idPeriod')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'a.idLender = w.id')
            ->where(':duration BETWEEN pp.min AND pp.max')
            ->setParameter('duration', $duration)
            ->andWhere('a.status = :autobidSettingActive')
            ->setParameter('autobidSettingActive', Autobid::STATUS_ACTIVE)
            ->andWhere('pp.status = :projectPeriodActive')
            ->setParameter('projectPeriodActive', \project_period::STATUS_ACTIVE)
            ->andWhere('prs.status = :projectRateSettingActive')
            ->setParameter('projectRateSettingActive', \project_rate_settings::STATUS_ACTIVE)
            ->andWhere('a.evaluation = :evaluation')
            ->andWhere('prs.evaluation = :evaluation')
            ->setParameter('evaluation', $evaluation)
            ->andWhere('a.rateMin <= prs.rateMax')
            ->andWhere('w.availableBalance >= a.amount');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int|Wallet $lender
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function getLastValidationDate($lender)
    {
        $queryBuilder = $this->createQueryBuilder('a');
        $queryBuilder->select('MAX(IFNULL(a.updated, a.added))')
            ->where('a.idLender = :wallet')
            ->andWhere('a.status != :statusArchived')
            ->setParameter('wallet', $lender)
            ->setParameter('statusArchived', Autobid::STATUS_ARCHIVED);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param null|int|Wallet        $lenderId
     * @param null|string            $evaluation
     * @param null|int|ProjectPeriod $periodId
     * @param array|null             $status
     * @param array|null             $order
     * @param int|null               $limit
     * @param int|null               $offset
     *
     * @return array
     */
    public function getSettings(
        $lenderId = null,
        ?string $evaluation = null,
        $periodId = null,
        ?array $status = [Autobid::STATUS_ACTIVE],
        ?array $order = ['pp.min' => 'ASC', 'a.evaluation' => 'DESC'],
        ?int $limit = null,
        ?int $offset = null
    )
    {
        $queryBuilder = $this->createQueryBuilder('a');
        $queryBuilder
            ->select('
                a.idAutobid AS id_autobid,
                IDENTITY(a.idLender) AS id_lender,
                a.status,
                a.evaluation,
                pp.idPeriod AS id_period,
                a.rateMin AS rate_min,
                a.amount,
                a.added,
                a.updated,
                pp.min AS period_min,
                pp.max AS period_max,
                pp.status AS period_status,
                IDENTITY(w.idClient) AS id_client'
            )
            ->innerJoin('UnilendCoreBusinessBundle:ProjectPeriod', 'pp', Join::WITH, 'pp.idPeriod = a.idPeriod AND pp.status = :pp_status')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'w.id = a.idLender')
            ->setParameter('pp_status', \project_period::STATUS_ACTIVE);

        if ($lenderId !== null) {
            $queryBuilder->andWhere('a.idLender = :id_lender');
            $queryBuilder->setParameter('id_lender', $lenderId);
        }
        if ($evaluation !== null) {
            $queryBuilder->andWhere('a.evaluation = :evaluation');
            $queryBuilder->setParameter('evaluation', $evaluation);
        }
        if ($periodId !== null) {
            $queryBuilder->andWhere('a.idPeriod = :id_period');
            $queryBuilder->setParameter('id_period', $periodId);
        }
        if (false === empty($status)) {
            $queryBuilder->andWhere('a.status in (:status)');
            $queryBuilder->setParameter('status', $status, Connection::PARAM_STR_ARRAY);
        }
        if (false === empty($order)) {
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

        // @todo temporary patch
        if (null === $lenderId) {
            return $queryBuilder->getQuery()->getScalarResult();
        }

        $settings = [];

        foreach ($queryBuilder->getQuery()->getScalarResult() as $setting) {
            $settings[$setting['period_min'] . $setting['evaluation']] = $setting;
        }

        return $settings;
    }
}
