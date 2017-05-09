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
     *
     * @return array
     */
    public function getInactiveLenderWalletOnPeriod(\DateTime $inactiveSince, $minAvailableBalance = null)
    {
        $qb = $this->createQueryBuilder('w')
            ->select('MAX(wbh.added) AS lastOperationDate, IDENTITY(w.idClient) AS idClient, w.availableBalance, w.id AS walletId')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'wt.id = w.idType')
            ->innerJoin('UnilendCoreBusinessBundle:WalletBalanceHistory', 'wbh', Join::WITH, 'w.id = wbh.idWallet')
            ->innerJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.id = wbh.idOperation')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'ot.id = o.idType')
            ->leftJoin('UnilendCoreBusinessBundle:Bids', 'b', Join::WITH, 'b.idLenderAccount = w.id AND b.added >= :inactive_since')
            ->where('wt.label = :wallet_type')
            ->setParameter('wallet_type', WalletType::LENDER)
            ->andWhere('ot.label IN (:operation_type)')
            ->setParameter(':operation_type', [OperationType::LENDER_PROVISION, OperationType::LENDER_WITHDRAW]);

        if (null !== $minAvailableBalance) {
            $qb->andWhere('w.availableBalance >= :available_balance')
                ->setParameter('available_balance', $minAvailableBalance);
        }
        $qb->andWhere('b.idBid IS NULL')
            ->groupBy('w.id')
            ->having('lastOperationDate <= :inactive_since')
            ->setParameter('inactive_since', $inactiveSince);

        return $qb->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR);
    }


    /**
     * @param array  $operationTypes
     * @param int    $year
     *
     * @return array Wallet[]
     */
    public function getLenderWalletsWithOperationsInYear($operationTypes, $year)
    {
        $qb = $this->createQueryBuilder('w');
        $qb->innerJoin('UnilendCoreBusinessBundle:WalletBalanceHistory', 'wbh', Join::WITH, 'w.id = wbh.idWallet')
            ->innerJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.id = wbh.idOperation')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'ot.id = o.idType')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'wt.id = w.idType')
            ->where('wt.label = :lender')
            ->andWhere('ot.label IN (:operationTypes)')
            ->andWhere('YEAR(o.added) = :year')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('operationTypes', $operationTypes, Connection::PARAM_INT_ARRAY)
            ->setParameter('year', $year);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function getLendersWalletsWithLatePaymentsForIRR()
    {
        $now = new \DateTime('NOW');

        $qb = $this->createQueryBuilder('w')
            ->select('w')
            ->innerJoin('UnilendCoreBusinessBundle:Echeanciers', 'e', Join::WITH, 'w.id = e.idLender')
            ->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH, 'e.idProject = p.idProject')
            ->where('e.dateEcheance < :now')
            ->andWhere('e.status = 0')
            ->andWhere('p.status IN (:status)');

        $subQuery = $this->getEntityManager()->createQueryBuilder()
            ->add('select','MAX(ls.added)')
            ->add('from', 'UnilendCoreBusinessBundle:LenderStatistic ls')
            ->add('where', 'w.id = ls.idWallet');

        $qb->andWhere('(' . $subQuery->getDQL() . ') < e.dateEcheance')
            ->setParameter(':now', $now)
            ->setParameter(':status',[
                \projects_status::PROBLEME,
                \projects_status::PROBLEME_J_X,
                \projects_status::RECOUVREMENT
            ], Connection::PARAM_INT_ARRAY)
            ->groupBy('w.id');

        $query = $qb->getQuery();

        return $query->getResult();
    }
}
