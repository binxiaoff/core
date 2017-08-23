<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;

class ProjectRepaymentTaskRepository extends EntityRepository
{
    /**
     * @param \DateTime $repaymentDate
     * @param int       $limit
     *
     * @return ProjectRepaymentTask[]
     */
    public function getProjectsToRepay(\DateTime $repaymentDate, $limit)
    {
        $qb = $this->createQueryBuilder('prt');
        $qb->where('prt.status = :ready')
            ->andWhere('prt.repayAt <= :repaymentDate')
            ->setParameter('ready', ProjectRepaymentTask::STATUS_READY)
            ->setParameter('repaymentDate', $repaymentDate)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
