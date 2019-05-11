<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use Unilend\Entity\{Bids, Clients, Projects, Wallet};

/**
 * @method Bids|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bids|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bids[]    findAll()
 * @method Bids[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BidsRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bids::class);
    }

    /**
     * @param Bids $bid
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Bids $bid)
    {
        $this->getEntityManager()->persist($bid);
        $this->getEntityManager()->flush();
    }

    /**
     * @param array $criteria
     *
     * @throws NonUniqueResultException
     *
     * @return mixed
     */
    public function countBy(array $criteria = [])
    {
        $qb = $this->createQueryBuilder('b');
        $qb->select('COUNT(b)');
        if (false === empty($criteria)) {
            foreach ($criteria as $field => $value) {
                $qb->andWhere('b.' . $field . ' = :' . $field)
                    ->setParameter($field, $value)
                ;
            }
        }
        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * @param \DateTime   $from
     * @param \DateTime   $to
     * @param int|Clients $clientId
     *
     * @throws NonUniqueResultException
     *
     * @return int
     */
    public function countByClientInPeriod(\DateTime $from, \DateTime $to, $clientId)
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.idBid) AS bidNumber')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'w.id = b.wallet')
            ->where('b.added BETWEEN :fromDate AND :toDate')
            ->andWhere('w.idClient = :idClient')
            ->setParameters(['fromDate' => $from, 'toDate' => $to, 'idClient' => $clientId])
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet    $wallet
     * @param \DateTime $date
     *
     * @throws NonUniqueResultException
     *
     * @return int
     */
    public function getManualBidCountByDateAndWallet(Wallet $wallet, \DateTime $date)
    {
        $queryBuilder = $this->createQueryBuilder('b');
        $queryBuilder->select('COUNT(b.idBid)')
            ->where('b.wallet = :wallet')
            ->andWhere('b.idAutobid IS NULL')
            ->andWhere('b.added > :date')
            ->setParameter('wallet', $wallet)
            ->setParameter('date', $date)
        ;

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet|int   $wallet
     * @param Projects|int $project
     * @param array        $status
     *
     * @throws NonUniqueResultException
     *
     * @return int
     */
    public function getSumByWalletAndProjectAndStatus($wallet, $project, array $status)
    {
        $qb = $this->createQueryBuilder('b');
        $qb->select('SUM(b.amount) / 100')
            ->where('b.project = :project')
            ->andWhere('b.wallet = :wallet')
            ->andWhere('b.status IN (:status)')
            ->setParameter('wallet', $wallet)
            ->setParameter('project', $project)
            ->setParameter('status', $status)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet|int $wallet
     * @param int        $status
     *
     * @throws NonUniqueResultException
     *
     * @return mixed
     */
    public function getSumBidsForLenderAndStatus($wallet, int $status)
    {
        $qb = $this->createQueryBuilder('b');
        $qb->select('ROUND(SUM(b.amount) / 100, 2)')
            ->where('b.wallet = :wallet')
            ->andWhere('b.status = :status')
            ->setParameter('wallet', $wallet)
            ->setParameter('status', $status)
        ;

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet|int   $lenderWallet
     * @param Projects|int $project
     *
     * @throws NonUniqueResultException
     *
     * @return Bids|null
     */
    public function findFirstAutoBidByLenderAndProject($lenderWallet, $project)
    {
        $queryBuilder = $this->createQueryBuilder('b');
        $queryBuilder->where('b.wallet = :wallet')
            ->andWhere('b.project = :project')
            ->andWhere('b.idAutobid IS NOT NULL')
            ->setParameter('wallet', $lenderWallet)
            ->setParameter('project', $project)
            ->orderBy('b.idBid', 'ASC')
            ->addOrderBy('b.added', 'ASC')
            ->setMaxResults(1)
            ->setFirstResult(0)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Projects|int $project
     * @param int          $status
     * @param int          $limit
     * @param int          $offset
     *
     * @return Bids[]
     */
    public function getAutoBids($project, int $status, int $limit = 100, int $offset = 0): array
    {
        $queryBuilder = $this->createQueryBuilder('b');
        $queryBuilder
            ->where('b.project = :project')
            ->andWhere('b.status = :status')
            ->andWhere('b.idAutobid IS NOT NULL')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->setParameters(['project' => $project, 'status' => $status])
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Projects|int $project
     *
     * @throws NonUniqueResultException
     *
     * @return float|null
     */
    public function getProjectMaxRate($project): ?float
    {
        $queryBuilder = $this->createQueryBuilder('b');
        $queryBuilder
            ->select('MAX(b.rate)')
            ->where('b.project = :project')
            ->andWhere('b.status = :status')
            ->setParameter('project', $project)
            ->setParameter('status', Bids::STATUS_PENDING)
        ;

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Projects|int $project
     * @param float|null   $rate
     * @param array        $status
     *
     * @throws NonUniqueResultException
     *
     * @return float
     */
    public function getProjectTotalAmount($project, ?float $rate = null, array $status = []): float
    {
        $queryBuilder = $this->createQueryBuilder('b');
        $queryBuilder
            ->select('IFNULL(SUM(b.amount) / 100, 0)')
            ->where('b.project = :project')
            ->setParameter('project', $project)
        ;

        if (false === empty($rate)) {
            $queryBuilder->andWhere('ROUND(b.rate, 1) = ROUND(:rate, 1)');
            $queryBuilder->setParameter('rate', $rate);
        }

        if (false === empty($status)) {
            $queryBuilder->andWhere('b.status IN (:status)');
            $queryBuilder->setParameter('status', $status, Connection::PARAM_STR_ARRAY);
        }

        return (float) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
