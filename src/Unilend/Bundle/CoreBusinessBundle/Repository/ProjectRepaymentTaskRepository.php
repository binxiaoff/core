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
        $qb->select('prt, IFNULL(prt.repayAt, e.dateEcheance) AS HIDDEN repaymentDate')
            ->innerJoin('UnilendCoreBusinessBundle:Echeanciers', 'e', Join::WITH, 'prt.idProject = e.idProject AND prt.sequence = e.ordre')
            ->where('prt.status = :ready')
            ->having('DATE(repaymentDate) <= :repaymentDate')
            ->setParameter('ready', ProjectRepaymentTask::STATUS_READY)
            ->setParameter('repaymentDate', $repaymentDate->format('Y-m-d'))
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
