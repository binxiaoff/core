<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectEligibilityRule;
use Unilend\Bundle\CoreBusinessBundle\Entity\RiskDataMonitoringType;

class RiskDataMonitoringTypeRepository extends EntityRepository
{

    /**
     * @param string|ProjectEligibilityRule|int $value
     *
     * @return null|RiskDataMonitoringType
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findRatingTypeByCompanyRatingOrRule($value) : ?RiskDataMonitoringType
    {
        $queryBuilder = $this->createQueryBuilder('rdmt');
        $queryBuilder
            ->leftJoin('UnilendCoreBusinessBundle:ProjectEligibilityRule', 'per', Join::WITH, 'per.id = rdmt.idProjectEligibilityRule')
            ->where('rdmt.companyRating = :companyRating')
            ->orWhere('per.label = :rule')
            ->setParameter('companyRating', $value)
            ->setParameter('rule', $value);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
