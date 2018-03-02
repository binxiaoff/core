<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoringCallLog;

class RiskDataMonitoringCallLogRepository extends EntityRepository
{
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
            ->innerJoin('UnilendCoreBusinessBundle:RiskDataMonitoring', 'rdm', Join::WITH, 'rdmcl.idRiskDataMonitoring =  rdm.id')
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
            ->innerJoin('UnilendCoreBusinessBundle:RiskDataMonitoring', 'rdm', Join::WITH, 'rdmcl.idRiskDataMonitoring =  rdm.id')
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
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:RiskDataMonitoring', 'rdm', Join::WITH, 'rdmcl.idRiskDataMonitoring =  rdm.id')
            ->where('rdm.provider = :provider')
            ->orderBy('rdmcl.added', 'DESC')
            ->setMaxResults(1)
            ->setParameter('provider', $provider);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
