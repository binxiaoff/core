<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class DebtCollectionMissionRepository extends EntityRepository
{
    /**
     * @param Clients|int $debtCollector
     * @param bool        $includeArchived
     *
     * @return mixed
     */
    public function getCountMissionsByDebtCollector($debtCollector, $includeArchived = false)
    {
        $queryBuilder = $this->createQueryBuilder('dcm')
            ->select('COUNT(DISTINCT dcm.idProject) AS entrustedProjects')
            ->where('dcm.idClientDebtCollector = :idClientDebtCollector')
            ->setParameter('idClientDebtCollector', $debtCollector);

        if (false === $includeArchived) {
            $queryBuilder->andWhere('dcm.archived IS NOT NULL');
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Clients|int $debtCollector
     *
     * @return mixed
     */
    public function getAmountMissionsByDebtCollector($debtCollector)
    {
        $queryBuilder = $this->createQueryBuilder('dcm')
            ->select('SUM(CASE WHEN dcm.archived IS NULL THEN (dcm.capital + dcm.interest + dcm.commissionVatIncl) ELSE 0 END) AS entrustedAmount')
            ->where('dcm.idClientDebtCollector = :idClientDebtCollector')
            ->setParameter('idClientDebtCollector', $debtCollector);

        return round($queryBuilder->getQuery()->getSingleScalarResult(), 2);
    }
}
