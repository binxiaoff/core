<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use Unilend\Entity\{AcceptedBids, Bids, Loans, Projects, Wallet};

class AcceptedBidsRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcceptedBids::class);
    }

    /**
     * @param AcceptedBids $acceptedBid
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(AcceptedBids $acceptedBid): void
    {
        $this->getEntityManager()->persist($acceptedBid);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Wallet|int   $wallet
     * @param Projects|int $project
     *
     * @return AcceptedBids[]
     */
    public function findAcceptedBidsByLenderAndProject($wallet, $project): array
    {
        $queryBuilder = $this->createQueryBuilder('ab');
        $queryBuilder
            ->innerJoin(Bids::class, 'b', Join::WITH, 'ab.idBid = b.idBid')
            ->where('b.wallet = :wallet')
            ->andWhere('b.project = :project')
            ->orderBy('b.rate', 'DESC')
            ->setParameter('wallet', $wallet)
            ->setParameter('project', $project)
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Loans|int $loan
     *
     * @throws NonUniqueResultException
     *
     * @return int
     */
    public function getCountAcceptedBidsByLoan($loan): int
    {
        $queryBuilder = $this->createQueryBuilder('ab');
        $queryBuilder
            ->select('COUNT(ab.idBid)')
            ->where('ab.idLoan = :loan')
            ->setParameter('loan', $loan)
        ;

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet|int   $wallet
     * @param Projects|int $project
     *
     * @throws NonUniqueResultException
     *
     * @return int
     */
    public function getDistinctBidsForLenderAndProject($wallet, $project): int
    {
        $queryBuilder = $this->createQueryBuilder('ab');
        $queryBuilder
            ->select('COUNT(ab.idBid)')
            ->innerJoin(Loans::class, 'l', Join::WITH, 'ab.idLoan = l.idLoan')
            ->where('l.wallet = :lender')
            ->andWhere('l.project = :project')
            ->setParameter('lender', $wallet)
            ->setParameter('project', $project)
        ;

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
