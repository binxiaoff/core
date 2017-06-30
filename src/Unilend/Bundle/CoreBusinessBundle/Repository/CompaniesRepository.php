<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;

class CompaniesRepository extends EntityRepository
{
    /**
     * @param int $maxDepositAmount
     *
     * @return array
     */
    public function getLegalEntitiesByCumulativeDepositAmount($maxDepositAmount)
    {
        $operationType = $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType');
        $queryBuilder  = $this->createQueryBuilder('c')
            ->select('c.idClientOwner AS idClient, c.capital, SUM(o.amount) AS depositAmount, GROUP_CONCAT(o.id) AS operation')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClientOwner = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.idWalletCreditor = w.id')
            ->where('o.idType = :operation_type')
            ->setParameter('operation_type', $operationType->findOneBy(['label' => OperationType::LENDER_PROVISION]))
            ->groupBy('o.idWalletCreditor')
            ->having('depositAmount >= c.capital')
            ->andHaving('depositAmount >= :max_deposit_amount')
            ->setParameter('max_deposit_amount', $maxDepositAmount);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $siren
     * @param string $ratingType
     * @param bool   $ongoing
     *
     * @return array
     */
    public function getMonitoredCompaniesBySirenAndRatingType($siren, $ratingType, $ongoing = true)
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:RiskDataMonitoring', 'rdm', Join::WITH, 'c.siren = rdm.siren')
            ->where('c.siren = :siren')
            ->andWhere('rdm.ratingType = :ratingType')
            ->setParameter('siren', $siren)
            ->setParameter('ratingType', $ratingType);

        if ($ongoing) {
            $queryBuilder->andWhere('rdm.end IS NULL');
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
