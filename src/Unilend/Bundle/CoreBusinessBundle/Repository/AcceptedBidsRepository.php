<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\{EntityRepository, NoResultException};
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\{AcceptedBids, Loans, Projects, Wallet};

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
            ->innerJoin('UnilendCoreBusinessBundle:Bids', 'b', Join::WITH, 'ab.idBid = b.idBid')
            ->where('b.idLenderAccount = :wallet')
            ->andWhere('b.idProject = :project')
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
            ->innerJoin('UnilendCoreBusinessBundle:Loans', 'l', Join::WITH, 'ab.idLoan = l.idLoan')
            ->where('l.idLender = :lender')
            ->andWhere('l.idProject = :project')
            ->setParameter('lender', $wallet)
            ->setParameter('project', $project);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
