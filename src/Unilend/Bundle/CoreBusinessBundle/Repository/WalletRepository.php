<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class WalletRepository extends EntityRepository
{

    /**
     * @return array Wallet[]
     */
    public function getTaxWallets()
    {
        $cb = $this->createQueryBuilder('w');
        $cb->select('w')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->where('wt.label IN (:taxWallets)')
            ->setParameter(
                'taxWallets', [
                WalletType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE,
                WalletType::TAX_FR_ADDITIONAL_CONTRIBUTIONS,
                WalletType::TAX_FR_CRDS,
                WalletType::TAX_FR_CSG,
                WalletType::TAX_FR_SOLIDARITY_DEDUCTIONS,
                WalletType::TAX_FR_STATUTORY_CONTRIBUTIONS,
                WalletType::TAX_FR_SOCIAL_DEDUCTIONS
            ], Connection::PARAM_INT_ARRAY);
        $query = $cb->getQuery();

        return $query->getResult();
    }

    /**
     * @param integer|Clients   $idClient
     * @param string|WalletType $walletType
     *
     * @return Wallet|null
     */
    public function getWalletByType($idClient, $walletType)
    {
        $cb = $this->createQueryBuilder('w');
        $cb->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->where('w.idClient = :idClient')
            ->andWhere('wt.label = :walletType')
            ->setMaxResults(1)
            ->setParameters(['idClient' => $idClient, 'walletType' => $walletType]);
        $query  = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @param \DateTime  $inactiveSince
     * @param null|float $minAvailableBalance
     * @return array
     */
    public function getInactiveLenderWalletOnPeriod(\DateTime $inactiveSince, $minAvailableBalance = null)
    {
        $qb = $this->createQueryBuilder('w')
            ->select('MAX(wbh.added) AS lastOperationDate, IDENTITY(w.idClient) AS idClient, w.availableBalance, w.id AS walletId')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'wt.id = w.idType')
            ->innerJoin('UnilendCoreBusinessBundle:WalletBalanceHistory', 'wbh', Join::WITH, 'w.id = wbh.idWallet')
            ->leftJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.id = wbh.idOperation')
            ->leftJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'ot.id = o.idType AND ot.label IN (:operationType)')
            ->leftJoin('UnilendCoreBusinessBundle:Bids', 'b', Join::WITH, 'b.idBid = wbh.idBid AND b.idAutobid IS NULL')
            ->where('wt.label = :lender')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter(':operationType', [OperationType::LENDER_PROVISION, OperationType::LENDER_WITHDRAW], Connection::PARAM_INT_ARRAY);

        if (null !== $minAvailableBalance) {
            $qb->andWhere('w.availableBalance >= :availableBalance')
                ->setParameter('availableBalance', $minAvailableBalance);
        }
        $qb->groupBy('w.id')
            ->having('lastOperationDate <= :inactiveSince')
            ->setParameter('inactiveSince', $inactiveSince);

        return $qb->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR);
    }
}
