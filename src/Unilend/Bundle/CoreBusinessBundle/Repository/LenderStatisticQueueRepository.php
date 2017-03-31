<?php


namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class LenderStatisticQueueRepository extends EntityRepository
{

    /**
     * @param int $limit
     */
    public function getLenderFromQueue($limit)
    {
        $qb = $this->createQueryBuilder('lsq');
        $qb->orderBy('lsq.added', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
