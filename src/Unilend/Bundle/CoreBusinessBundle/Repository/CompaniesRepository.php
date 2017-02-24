<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class CompaniesRepository extends EntityRepository
{
    /**
     * @param int $maxDepositAmount
     * @return array
     */
    public function getLegalEntitiesByCumulativeDepositAmount($maxDepositAmount = 15000)
    {
        $walletType = $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:WalletType');

        $qb = $this->createQueryBuilder('c')
            ->select('c.idClientOwner, c.capital, SUM(o.amount) as cumulative_deposit_amount')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClientOwner = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'wt.id = w.idType')
            ->innerJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.idWalletCreditor = w.id')
            ->where('wt.label = :wallet_type')
            ->setParameter('wallet_type', $walletType->findOneBy(['label' => WalletType::LENDER]))
            ->groupBy('o.idWalletCreditor')
            ->having('cumulative_deposit_amount >= c.capital')
            ->andHaving('cumulative_deposit_amount >= :max_deposit_amount')
            ->setParameter('max_deposit_amount', $maxDepositAmount);

        return $qb->getQuery()->getResult();
    }
}
