<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class WireTransferOutRepository extends EntityRepository
{
    /**
     * @param           $status
     * @param \DateTime $dateTime
     *
     * @return array
     */
    public function findBefore($status, \DateTime $dateTime)
    {
        $qb = $this->createQueryBuilder('wto');
        $qb->where('wto.status = :status')
           ->andWhere('wto.added <= :added')
           ->setParameter('status', $status)
           ->setParameter('added', $dateTime);

        return $qb->getQuery()->getResult();
    }
}
