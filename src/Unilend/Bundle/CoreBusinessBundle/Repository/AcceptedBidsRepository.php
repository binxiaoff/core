<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;


class AcceptedBidsRepository extends EntityRepository
{

    public function findAcceptedBidsByLender($wallet)
    {
        $queryBuilder = $this->createQueryBuilder('ab');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:Bids', 'b', Join::WITH, 'ab.idBid = b.idBid')
            ->where('b.idLenderAccount = :wallet')
            ->orderBy('b.rate', 'DESC')
            ->setParameter('wallet', $wallet);

        return $queryBuilder->getQuery()->getResult();
    }
}
