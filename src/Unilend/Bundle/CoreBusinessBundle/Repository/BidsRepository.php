<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\{EntityRepository, NonUniqueResultException, NoResultException, Query\Expr\Join};
use Unilend\Entity\{Bids, Clients, Projects, Wallet};

class BidsRepository extends EntityRepository
{
    /**
     * @param array $criteria
     *
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countBy(array $criteria = [])
    {
        $qb = $this->createQueryBuilder("b");
        $qb->select('COUNT(b)');
        if (false === empty($criteria)) {
            foreach ($criteria as $field => $value) {
                $qb->andWhere('b.' . $field . ' = :' . $field)
                    ->setParameter($field, $value);
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
     * @return integer
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countByClientInPeriod(\DateTime $from, \DateTime $to, $clientId)
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.idBid) AS bidNumber')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'w.id = b.wallet')
            ->where('b.added BETWEEN :fromDate AND :toDate')
            ->andWhere('w.idClient = :idClient')
            ->setParameters(['fromDate' => $from, 'toDate' => $to, 'idClient' => $clientId]);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet    $wallet
     * @param \DateTime $date
     *
     * @return integer
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getManualBidCountByDateAndWallet(Wallet $wallet, \DateTime $date)
    {
        $queryBuilder = $this->createQueryBuilder('b');
        $queryBuilder->select('COUNT(b.idBid)')
            ->where('b.wallet = :wallet')
            ->andWhere('b.idAutobid IS NULL')
            ->andWhere('b.added > :date')
            ->setParameter('wallet', $wallet)
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet|int   $wallet
     * @param Projects|int $project
     * @param array        $status
     *
     * @return integer
     * @throws NoResultException
     * @throws NonUniqueResultException
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
            ->setParameter('status', $status);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet|int $wallet
     * @param int        $status
     *
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getSumBidsForLenderAndStatus($wallet, int $status)
    {
        $qb = $this->createQueryBuilder('b');
        $qb->select('ROUND(SUM(b.amount) / 100, 2)')
            ->where('b.wallet = :wallet')
            ->andWhere('b.status = :status')
            ->setParameter('wallet', $wallet)
            ->setParameter('status', $status);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet|int   $lenderWallet
     * @param Projects|int $project
     *
     * @return Bids|null
     * @throws NonUniqueResultException
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
            ->setFirstResult(0);

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
            ->setParameters(['project' => $project, 'status' => $status]);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Projects|int $project
     *
     * @return float|null
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getProjectMaxRate($project): ?float
    {
        $queryBuilder = $this->createQueryBuilder('b');
        $queryBuilder
            ->select('MAX(b.rate)')
            ->where('b.project = :project')
            ->andWhere('b.status = :status')
            ->setParameter('project', $project)
            ->setParameter('status', Bids::STATUS_PENDING);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Projects|int $project
     * @param float|null   $rate
     * @param array        $status
     *
     * @return float
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getProjectTotalAmount($project, ?float $rate = null, array $status = []): float
    {
        $queryBuilder = $this->createQueryBuilder('b');
        $queryBuilder
            ->select('IFNULL(SUM(b.amount) / 100, 0)')
            ->where('b.project = :project')
            ->setParameter('project', $project);

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
