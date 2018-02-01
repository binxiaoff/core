<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoringCallLog;

class RiskDataMonitoringCallLogRepository extends EntityRepository
{
    /**
     * @param $siren
     *
     * @return array
     */
    public function findCallLogsForSiren(string $siren) : array
    {
        $queryBuilder = $this->createQueryBuilder('rdmcl');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:RiskDataMonitoring', 'rdm', Join::WITH, 'rdmcl.idRiskDataMonitoring =  rdm.id')
            ->where('rdm.siren = :siren')
            ->setParameter('siren', $siren);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string    $siren
     * @param \DateTime $date
     *
     * @return array
     */
    public function findCallLogsForSirenAfterDate(string $siren, \DateTime $date) : array
    {
        $queryBuilder = $this->createQueryBuilder('rdmcl');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:RiskDataMonitoring', 'rdm', Join::WITH, 'rdmcl.idRiskDataMonitoring =  rdm.id')
            ->where('rdm.siren = :siren')
            ->andWhere('rdmcl.added > :date')
            ->setParameter('siren', $siren)
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $provider
     *
     * @return RiskDataMonitoringCallLog
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastCallLogForProvider(string $provider) : ?RiskDataMonitoringCallLog
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
