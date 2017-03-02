<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;

class CompaniesRepository extends EntityRepository
{
    /**
     * @param int $maxDepositAmount
     * @return array
     */
    public function getLegalEntitiesByCumulativeDepositAmount($maxDepositAmount = 15000)
    {
        $operationType = $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType');
        $qb            = $this->createQueryBuilder('c')
            ->select('c.idClientOwner AS idClient, c.capital, SUM(o.amount) AS depositAmount, o.id AS operation')// @todo find a way to do a group_concat
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClientOwner = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.idWalletCreditor = w.id')
            ->where('o.idType = :operation_type')
            ->setParameter('operation_type', $operationType->findOneBy(['label' => OperationType::LENDER_PROVISION]))
            ->groupBy('o.idWalletCreditor')
            ->having('depositAmount >= c.capital')
            ->andHaving('depositAmount >= :max_deposit_amount')
            ->setParameter('max_deposit_amount', $maxDepositAmount);

        return $qb->getQuery()->getResult();
    }
}
