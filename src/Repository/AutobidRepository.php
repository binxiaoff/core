<?php

namespace Unilend\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\{EntityRepository, ORMException};
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{Autobid, Bids, Clients, ClientSettings, ClientSettingType, ClientsStatus, ClientsStatusHistory, ProjectPeriod, ProjectRateSettings, Projects, Wallet, WalletType};

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
            ->innerJoin(ProjectPeriod::class, 'pp', Join::WITH, 'a.idPeriod = pp.idPeriod')
            ->innerJoin(ProjectRateSettings::class, 'prs', Join::WITH, 'pp.idPeriod = prs.idPeriod')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'a.idLender = w.id')
            ->where(':duration BETWEEN pp.min AND pp.max')
            ->setParameter('duration', $duration)
            ->andWhere('a.status = :autobidSettingActive')
            ->setParameter('autobidSettingActive', Autobid::STATUS_ACTIVE)
            ->andWhere('pp.status = :projectPeriodActive')
            ->setParameter('projectPeriodActive', ProjectPeriod::STATUS_ACTIVE)
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
            ->innerJoin(ProjectPeriod::class, 'pp', Join::WITH, 'pp.idPeriod = a.idPeriod AND pp.status = :pp_status')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'w.id = a.idLender')
            ->setParameter('pp_status', ProjectPeriod::STATUS_ACTIVE);

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
     *
     * @return Autobid[]
     */
    public function getAutobidsForProject(Projects $project): array
    {
        $queryBuilder = $this->createQueryBuilder('a');
        $queryBuilder
            ->select('a')
            ->innerJoin(ProjectPeriod::class, 'p', Join::WITH, 'a.idPeriod = p.idPeriod')
            ->innerJoin(ProjectRateSettings::class, 'prs', Join::WITH, 'a.rateMin <= prs.rateMax')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'a.idLender = w.id')
            ->innerJoin(WalletType::class, 'wt', Join::WITH, 'w.idType = wt.id AND wt.label = :walletType')
            ->innerJoin(Clients::class, 'c', Join::WITH, 'w.idClient = c.idClient')
            ->innerJoin(ClientsStatusHistory::class, 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
            ->innerJoin(ClientSettings::class, 'cs', Join::WITH, 'w.idClient = cs.idClient')
            ->innerJoin(ClientSettingType::class, 'cst', Join::WITH, 'cs.idType = cst.idType AND cst.label = :settingType')
            ->leftJoin(Bids::class, 'b', Join::WITH, 'a.idAutobid = b.idAutobid AND b.project = :project')
            ->where('a.status = :bidStatus')
            ->andWhere('p.status = :periodStatus')
            ->andWhere('p.min <= :projectDuration')
            ->andWhere('p.max >= :projectDuration')
            ->andWhere('prs.idRate = :rateId')
            ->andWhere('a.evaluation = :evaluation')
            ->andWhere('w.availableBalance >= a.amount')
            ->andWhere('csh.idStatus = :clientStatus')
            ->andWhere('cs.value = :clientSetting')
            ->andWhere('b.idBid IS NULL')
            ->setParameter('walletType', WalletType::LENDER)
            ->setParameter('settingType', ClientSettingType::LABEL_AUTOBID_SWICTH)
            ->setParameter('project', $project->getIdProject())
            ->setParameter('bidStatus', Autobid::STATUS_ACTIVE)
            ->setParameter('periodStatus', ProjectPeriod::STATUS_ACTIVE)
            ->setParameter('projectDuration', $project->getPeriod())
            ->setParameter('rateId', $project->getIdRate())
            ->setParameter('evaluation', $project->getRisk())
            ->setParameter('clientStatus', ClientsStatus::STATUS_VALIDATED)
            ->setParameter('clientSetting', ClientSettingType::TYPE_AUTOBID_SWITCH)
            ->orderBy('a.idAutobid', 'ASC');

        return $queryBuilder->getQuery()->getResult();
    }
}
