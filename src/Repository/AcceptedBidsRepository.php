<?php

namespace Unilend\Repository;

use Doctrine\ORM\{EntityRepository, NoResultException};
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{AcceptedBids, Bids, Loans, Projects, Wallet};

class AcceptedBidsRepository extends EntityRepository
{

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
            ->setParameter('project', $project);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Loans|int $loan
     *
     * @return int
     * @throws NoResultException
     */
    public function getCountAcceptedBidsByLoan($loan): int
    {
        $queryBuilder = $this->createQueryBuilder('ab');
        $queryBuilder
            ->select('COUNT(ab.idBid)')
            ->where('ab.idLoan = :loan')
            ->setParameter('loan', $loan);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet|int   $wallet
     * @param Projects|int $project
     *
     * @return int
     * @throws NoResultException
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
            ->setParameter('project', $project);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
