<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
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
            ->setParameter('taxWallets', WalletType::TAX_FR_WALLETS, Connection::PARAM_STR_ARRAY);
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
     * @param \DateTime $inactiveSince
     * @param int       $minAvailableBalance
     *
     * @return array
     */
    public function getInactiveLenderWalletOnPeriod(\DateTime $inactiveSince, $minAvailableBalance)
    {
        $sql = '
            SELECT
              a.walletId,
              GREATEST(a.lastOperationDate, b2.lastOperationDate) AS lastOperationDate,
              w.available_balance AS availableBalance
            FROM (
              SELECT
                COALESCE(o.id_wallet_creditor, o.id_wallet_debtor) AS walletId,
                MAX(o.added)                                       AS lastOperationDate
              FROM operation o
              INNER JOIN operation_type ot ON o.id_type = ot.id AND ot.label IN (:operationType)
              GROUP BY walletId
              HAVING lastOperationDate < :inactiveSince
            ) a
            INNER JOIN wallet w ON a.walletId = w.id AND w.available_balance >= :minAvailableBalance
            INNER JOIN wallet_type wt ON wt.id = w.id_type AND wt.label = :lender
            INNER JOIN (
              SELECT
                b.id_lender_account AS walletId,
                MAX(b.added)   AS lastOperationDate
              FROM bids b
              WHERE b.id_autobid IS NULL
              GROUP BY b.id_lender_account
              HAVING lastOperationDate < :inactiveSince
            ) b2 ON b2.walletId = a.walletId
            GROUP BY walletId';

        $params = [
            'operationType'       => [OperationType::LENDER_WITHDRAW, OperationType::LENDER_PROVISION],
            'lender'              => WalletType::LENDER,
            'inactiveSince'       => $inactiveSince->format('Y-m-d H:i:s'),
            'minAvailableBalance' => $minAvailableBalance
        ];
        $binds  = [
            'operationType'       => Connection::PARAM_STR_ARRAY,
            'lender'              => \PDO::PARAM_STR,
            'inactiveSince'       => \PDO::PARAM_STR,
            'minAvailableBalance' => \PDO::PARAM_INT
        ];

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($sql, $params, $binds)
            ->fetchAll();
    }

    /**
     * @param array $operationTypes
     * @param int   $year
     *
     * @return array Wallet[]
     */
    public function getLenderWalletsWithOperationsInYear(array $operationTypes, $year)
    {
        $qb = $this->createQueryBuilder('w');
        $qb->innerJoin('UnilendCoreBusinessBundle:WalletBalanceHistory', 'wbh', Join::WITH, 'w.id = wbh.idWallet')
            ->innerJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.id = wbh.idOperation')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'ot.id = o.idType')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'wt.id = w.idType')
            ->where('wt.label = :lender')
            ->andWhere('ot.label IN (:operationTypes)')
            ->andWhere('o.added BETWEEN :start AND :end')
            ->groupBy('w.id')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('operationTypes', $operationTypes, Connection::PARAM_STR_ARRAY)
            ->setParameter('start', $year . '-01-01 00:00:00')
            ->setParameter('end', $year . '-12-31- 23:59:59');

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
            ->add('select', 'MAX(ls.added)')
            ->add('from', 'UnilendCoreBusinessBundle:LenderStatistic ls')
            ->add('where', 'w.id = ls.idWallet');

        $qb->andWhere('(' . $subQuery->getDQL() . ') < e.dateEcheance')
            ->setParameter(':now', $now)
            ->setParameter(':status', [
                ProjectsStatus::PROBLEME
            ], Connection::PARAM_INT_ARRAY)
            ->groupBy('w.id');

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @param Wallet|int $wallet
     * @param float      $amount
     *
     * @return int
     */
    public function creditAvailableBalance($wallet, $amount)
    {
        if ($wallet instanceof Wallet) {
            $wallet = $wallet->getId();
        }

        $update = 'UPDATE wallet SET available_balance = available_balance + :amount WHERE id = :walletId';

        return $this->getEntityManager()->getConnection()->executeUpdate($update, ['amount' => $amount, 'walletId' => $wallet]);
    }

    /**
     * @param Wallet|int $wallet
     * @param float      $amount
     *
     * @return int
     */
    public function debitAvailableBalance($wallet, $amount)
    {
        if ($wallet instanceof Wallet) {
            $wallet = $wallet->getId();
        }

        $update = 'UPDATE wallet SET available_balance = available_balance - :amount WHERE id = :walletId';

        return $this->getEntityManager()->getConnection()->executeUpdate($update, ['amount' => $amount, 'walletId' => $wallet]);
    }

    /**
     * @param Wallet|int $wallet
     * @param float      $amount
     *
     * @return int
     */
    public function debitCommittedBalance($wallet, $amount)
    {
        if ($wallet instanceof Wallet) {
            $wallet = $wallet->getId();
        }

        $update = 'UPDATE wallet SET committed_balance = committed_balance - :amount WHERE id = :walletId';

        return $this->getEntityManager()->getConnection()->executeUpdate($update, ['amount' => $amount, 'walletId' => $wallet]);
    }

    /**
     * @param Wallet|int $wallet
     * @param float      $amount
     *
     * @return int
     */
    public function releaseBalance($wallet, $amount)
    {
        if ($wallet instanceof Wallet) {
            $wallet = $wallet->getId();
        }

        $update = 'UPDATE wallet SET committed_balance = committed_balance - :amount, available_balance = available_balance + :amount WHERE id = :walletId';

        return $this->getEntityManager()->getConnection()->executeUpdate($update, ['amount' => $amount, 'walletId' => $wallet]);
    }

    /**
     * @param Wallet|int $wallet
     * @param float      $amount
     *
     * @return int
     */
    public function engageBalance($wallet, $amount)
    {
        if ($wallet instanceof Wallet) {
            $wallet = $wallet->getId();
        }

        $update = 'UPDATE wallet SET available_balance = available_balance - :amount, committed_balance = committed_balance + :amount WHERE id = :walletId';

        return $this->getEntityManager()->getConnection()->executeUpdate($update, ['amount' => $amount, 'walletId' => $wallet]);
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function findLendersWithProvisionButWithoutAcceptedBidBetweenDates(\DateTime $start, \DateTime $end)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $queryBuilder = $this->createQueryBuilder('w');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.idWalletCreditor = w.id')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
            ->innerJoin('UnilendCoreBusinessBundle:Clients', 'c', Join::WITH, 'c.idClient = w.idClient')
            ->where('ot.label = :lenderProvision')
            ->andwhere('w.id NOT IN (SELECT IDENTITY(b.idLenderAccount) FROM Unilend\Bundle\CoreBusinessBundle\Entity\Bids b WHERE b.status = :accepted AND b.added BETWEEN :start AND :end)')
            ->groupBy('w.id')
            ->setParameter('lenderProvision', OperationType::LENDER_PROVISION)
            ->setParameter('accepted', Bids::STATUS_BID_ACCEPTED)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        return $queryBuilder->getQuery()->getResult();
    }
}
