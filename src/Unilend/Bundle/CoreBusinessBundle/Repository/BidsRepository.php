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

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @param int       $clientId
     *
     * @return mixed
     */
    public function countByClientInPeriod(\DateTime $from, \DateTime $to, $clientId)
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.idBid) AS bidNumber')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'w.id = b.idLenderAccount')
            ->where('b.added BETWEEN :fromDate AND :toDate')
            ->andWhere('w.idClient = :idClient')
            ->setParameters(['fromDate' => $from, 'toDate' => $to, 'idClient' => $clientId]);

        $bidCount =  $qb->getQuery()->getScalarResult();

        return $bidCount;
    }
}
