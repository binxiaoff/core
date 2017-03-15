<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use \Doctrine\ORM\Query\Expr\Join;

class BidsRepository extends EntityRepository
{
    /**
     * @param array $criteria
     *
     * @return mixed
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

    public function countByClientInPeriod(\DateTime $from, \DateTime $to, $clientId)
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.idBid) AS bidNumber')
            ->innerJoin('UnilendCoreBusinessBundle:LendersAccounts', 'la', Join::WITH, 'la.idLenderAccount = b.idLenderAccount')
            ->where('b.added BETWEEN :fromDate AND :toDate')
            ->andWhere('la.idClientOwner = :idClientOwner')
            ->setParameters(['fromDate' => $from, 'toDate' => $to, 'idClientOwner' => $clientId]);

        $bidCount =  $qb->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

        return $bidCount;
    }
}
