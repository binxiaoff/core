<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class AcceptedBidsRepository extends EntityRepository
{

    /**
     * @param Wallet|int   $wallet
     * @param Projects|int $project
     *
     * @return array
     */
    public function findAcceptedBidsByLenderAndProject($wallet, $project): array
    {
        $queryBuilder = $this->createQueryBuilder('ab');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:Bids', 'b', Join::WITH, 'ab.idBid = b.idBid')
            ->where('b.idLenderAccount = :wallet')
            ->andWhere('b.idProject = :project')
            ->orderBy('b.rate', 'DESC')
            ->orderBy('b.amount', 'ASC')
            ->setParameter('wallet', $wallet)
            ->setParameter('project', $project);

        return $queryBuilder->getQuery()->getResult();
    }
}
