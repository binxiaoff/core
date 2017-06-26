<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class RiskDataMonitoringRepository extends EntityRepository
{
    /**
     * @param string $siren
     * @param string $ratingType
     *
     * @return array
     */
    public function getOngoingMonitoredCompaniesBySirenAndRatingType($siren, $ratingType)
    {
        $qb = $this->createQueryBuilder('rdm');
        $qb->innerJoin('UnilendCoreBusinessBundle:Companies', 'co', Join::WITH, 'co.idCompany = rdm.idCompany')
            ->where('co.siren = :siren')
            ->andWhere('rdm.ratingType = :ratingType')
            ->andWhere('rdm.end IS NULL')
            ->setParameter('siren', $siren)
            ->setParameter('ratingType', $ratingType);

        return $qb->getQuery()->getResult();
    }
}
