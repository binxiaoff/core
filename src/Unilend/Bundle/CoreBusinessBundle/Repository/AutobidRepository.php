<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\{
    EntityRepository, ORMException
};
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Autobid, ClientSettingType, ClientsStatus, ProjectPeriod, ProjectRateSettings, Projects, Wallet, WalletType
};

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
            ->setParameter('projectRateSettingActive', ProjectRateSettings::STATUS_ACTIVE)
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

        return $queryBuilder->getQuery()->getScalarResult();
    }

    /**
     * @param Projects $project
     * @param float    $rate
     * @param int      $period
     *
     * @return Autobid[]
     */
    public function getAutobidsForProject(Projects $project, float $rate, int $period): array
    {
        // $rate and $period may be retrieved in the same query
        // For testing and performance purpose, we don't do that yet
        $queryBuilder = $this->createQueryBuilder('a');
        $queryBuilder
            ->select('a')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'a.idLender = w.id')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id AND wt.label = :walletType')
            ->innerJoin('UnilendCoreBusinessBundle:Clients', 'c', Join::WITH, 'w.idClient = c.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
            ->innerJoin('UnilendCoreBusinessBundle:ClientSettings', 'cs', Join::WITH, 'w.idClient = cs.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:ClientSettingType', 'cst', Join::WITH, 'cs.idType = cst.idType AND cst.label = :settingType')
            ->leftJoin('UnilendCoreBusinessBundle:Bids', 'b', Join::WITH, 'a.idAutobid = b.idAutobid AND b.idProject = :project')
            ->where('a.status = :bidStatus')
            ->andWhere('a.evaluation = :evaluation')
            ->andWhere('a.idPeriod = :period')
            ->andWhere('a.rateMin <= :rate')
            ->andWhere('w.availableBalance >= a.amount')
            ->andWhere('csh.idStatus = :clientStatus')
            ->andWhere('cs.value = 1')
            ->andWhere('b.idBid IS NULL')
            ->setParameter('walletType', WalletType::LENDER)
            ->setParameter('settingType', ClientSettingType::LABEL_AUTOBID_SWICTH)
            ->setParameter('project', $project->getIdProject())
            ->setParameter('bidStatus', Autobid::STATUS_ACTIVE)
            ->setParameter('evaluation', $project->getRisk())
            ->setParameter('period', $period)
            ->setParameter('rate', $rate)
            ->setParameter('clientStatus', ClientsStatus::STATUS_VALIDATED)
            ->orderBy('a.idAutobid', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }
}
