<?php

namespace Unilend\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Entity\{ProjectRepaymentTask, Receptions};

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
            ->andWhere('prt.type in (:supportedType)')
            ->setParameter('ready', ProjectRepaymentTask::STATUS_READY)
            ->setParameter('repaymentDate', $repaymentDate)
            ->setParameter('supportedType', [ProjectRepaymentTask::TYPE_REGULAR, ProjectRepaymentTask::TYPE_LATE, ProjectRepaymentTask::TYPE_EARLY, ProjectRepaymentTask::TYPE_CLOSE_OUT_NETTING])
            ->setMaxResults($limit)
            ->orderBy('prt.repayAt', 'ASC')
            ->orderBy('prt.type', 'ASC'); // Order by type so that we can do the early repayment at the end.

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Receptions|int $wireTransferIn
     *
     * @return float
     */
    public function getTotalCommissionByWireTransferIn($wireTransferIn)
    {
        $queryBuilder = $this->createQueryBuilder('prt');
        $queryBuilder->select('SUM(prt.commissionUnilend)')
            ->where('prt.idWireTransferIn = :wireTransferIn')
            ->andWhere('prt.status != :cancelled')
            ->setParameter('wireTransferIn', $wireTransferIn)
            ->setParameter('cancelled', ProjectRepaymentTask::STATUS_CANCELLED);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Receptions|int $wireTransferIn
     *
     * @return float
     */
    public function getTotalRepaymentByWireTransferIn($wireTransferIn)
    {
        $queryBuilder = $this->createQueryBuilder('prt');
        $queryBuilder->select('SUM(prt.capital + prt.interest)')
            ->where('prt.idWireTransferIn = :wireTransferIn')
            ->andWhere('prt.status != :cancelled')
            ->setParameter('wireTransferIn', $wireTransferIn)
            ->setParameter('cancelled', ProjectRepaymentTask::STATUS_CANCELLED);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
