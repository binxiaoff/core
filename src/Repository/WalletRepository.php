<?php

namespace Unilend\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{AcceptedBids, Bids, Clients, Echeanciers, Loans, Operation, OperationType, Projects, ProjectsStatus, Wallet, WalletBalanceHistory, WalletType};

class WalletRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wallet::class);
    }

    /**
     * @return array Wallet[]
     */
    public function getTaxWallets()
    {
        $cb = $this->createQueryBuilder('w');
        $cb->select('w')
            ->innerJoin(WalletType::class, 'wt', Join::WITH, 'w.idType = wt.id')
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
        $cb->innerJoin(WalletType::class, 'wt', Join::WITH, 'w.idType = wt.id')
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
              w.id                AS walletId,
              MAX(wbh.added)      AS lastOperationDate,
              w.available_balance AS availableBalance
            FROM wallet_balance_history wbh FORCE INDEX(fk_id_wallet_idx)
              LEFT JOIN operation o ON wbh.id_operation = o.id
              LEFT JOIN operation_type ot ON o.id_type = ot.id
              INNER JOIN wallet w ON w.id = wbh.id_wallet
              INNER JOIN wallet_type wt ON wt.id = w.id_type
              INNER JOIN clients c ON w.id_client = c.id_client
            WHERE wt.label = :lender
              AND w.available_balance >= :minAvailableBalance
              AND c.lastlogin < :inactiveSince
              AND (ot.label IN (:operationType) OR wbh.id_bid IS NOT NULL AND wbh.id_autobid IS NULL)
            GROUP BY w.id
            HAVING lastOperationDate < :inactiveSince';

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
        $qb->innerJoin(WalletBalanceHistory::class, 'wbh', Join::WITH, 'w.id = wbh.idWallet')
            ->innerJoin(Operation::class, 'o', Join::WITH, 'o.id = wbh.idOperation')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'ot.id = o.idType')
            ->innerJoin(WalletType::class, 'wt', Join::WITH, 'wt.id = w.idType')
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
            ->innerJoin(Echeanciers::class, 'e', Join::WITH, 'w.id = e.idLender')
            ->innerJoin(Projects::class, 'p', Join::WITH, 'e.idProject = p.idProject')
            ->where('e.dateEcheance < :now')
            ->andWhere('e.status = ' . Echeanciers::STATUS_PENDING)
            ->andWhere('p.status = ' . ProjectsStatus::STATUS_LOST);

        $subQuery = $this->getEntityManager()->createQueryBuilder()
            ->add('select', 'MAX(ls.added)')
            ->add('from', 'Uinlend\Entity\LenderStatistic ls')
            ->add('where', 'w.id = ls.idWallet');

        $qb->andWhere('(' . $subQuery->getDQL() . ') < e.dateEcheance')
            ->setParameter(':now', $now)
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
        $queryBuilder->innerJoin(Operation::class, 'o', Join::WITH, 'o.idWalletCreditor = w.id')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->innerJoin(Clients::class, 'c', Join::WITH, 'c.idClient = w.idClient')
            ->where('ot.label = :lenderProvision')
            ->andwhere('w.id NOT IN (SELECT IDENTITY(b.wallet) FROM Unilend\Entity\Bids b WHERE b.status = :accepted AND b.added BETWEEN :start AND :end)')
            ->groupBy('w.id')
            ->setParameter('lenderProvision', OperationType::LENDER_PROVISION)
            ->setParameter('accepted', Bids::STATUS_ACCEPTED)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Projects|int $project
     *
     * @return Wallet[]|array
     */
    public function findLendersWithAcceptedBidsByProject($project) : array
    {
        $queryBuilder = $this->createQueryBuilder('w');
        $queryBuilder
            ->innerJoin(Bids::class, 'b', Join::WITH, 'w.id = b.wallet')
            ->innerJoin(AcceptedBids::class, 'ab', Join::WITH, 'b.idBid = ab.idBid')
            ->where('b.project = :project')
            ->groupBy('w.id')
            ->setParameter('project', $project);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array $projects
     *
     * @return array
     */
    public function getLenderWalletsByProjects($projects) : array
    {
        $queryBuilder = $this->createQueryBuilder('w');
        $queryBuilder
            ->distinct()
            ->innerJoin(Loans::class, 'l', Join::WITH, 'l.wallet = w.id')
            ->where('l.project in (:projects)')
            ->setParameter('projects', $projects);

        return $queryBuilder->getQuery()->getResult();
    }
}
