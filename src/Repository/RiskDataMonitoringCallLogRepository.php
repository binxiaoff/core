<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{RiskDataMonitoring, RiskDataMonitoringCallLog};

class RiskDataMonitoringCallLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RiskDataMonitoringCallLog::class);
    }

    /**
     * @param string $siren
     *
     * @return string
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCountCallLogsForSiren(string $siren): string
    {
        $queryBuilder = $this->createQueryBuilder('rdmcl');
        $queryBuilder
            ->select('COUNT(DISTINCT rdmcl.id)')
            ->innerJoin(RiskDataMonitoring::class, 'rdm', Join::WITH, 'rdmcl.idRiskDataMonitoring =  rdm.id')
            ->where('rdm.siren = :siren')
            ->setParameter('siren', $siren);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string    $siren
     * @param \DateTime $date
     *
     * @return string
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCountCallLogsForSirenAfterDate(string $siren, \DateTime $date): string
    {
        $queryBuilder = $this->createQueryBuilder('rdmcl');
        $queryBuilder
            ->select('COUNT(DISTINCT rdmcl.id)')
            ->innerJoin(RiskDataMonitoring::class, 'rdm', Join::WITH, 'rdmcl.idRiskDataMonitoring =  rdm.id')
            ->where('rdm.siren = :siren')
            ->andWhere('rdmcl.added > :date')
            ->setParameter('siren', $siren)
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string $provider
     *
     * @return RiskDataMonitoringCallLog
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastCallLogForProvider(string $provider): ?RiskDataMonitoringCallLog
    {
        $queryBuilder = $this->createQueryBuilder('rdmcl');
        $queryBuilder->innerJoin(RiskDataMonitoring::class, 'rdm', Join::WITH, 'rdmcl.idRiskDataMonitoring =  rdm.id')
            ->where('rdm.provider = :provider')
            ->orderBy('rdmcl.added', 'DESC')
            ->setMaxResults(1)
            ->setParameter('provider', $provider);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
