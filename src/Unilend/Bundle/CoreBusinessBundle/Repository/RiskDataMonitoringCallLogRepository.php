<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class RiskDataMonitoringCallLogRepository extends EntityRepository
{
    /**
     * @param $siren
     *
     * @return array
     */
    public function findCallLogsForSiren($siren)
    {
        $queryBuilder = $this->createQueryBuilder('rdmcl');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:RiskDataMonitoring', 'rdm', Join::WITH, 'rdmcl.idRiskDataMonitoring =  rdm.id')
            ->where('rdm.siren = :siren')
            ->setParameter('siren', $siren);

        return $queryBuilder->getQuery()->getResult();
    }
}
