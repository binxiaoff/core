<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectStatusHistoryReason;

class ProjectStatusHistoryReasonRepository extends EntityRepository
{
    /**
     * @param Projects|int $project
     * @param string       $rejectionReasonLabel
     *
     * @return ProjectStatusHistoryReason|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findRejectionReasonByProjectAndLabel($project, string $rejectionReasonLabel)
    {
        $queryBuilder = $this->createQueryBuilder('pshr');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:ProjectRejectionReason', 'prr', Join::WITH, 'prr.idRejection = pshr.idRejectionReason')
            ->innerJoin('UnilendCoreBusinessBundle:ProjectsStatusHistory', 'psh', Join::WITH, 'psh.idProjectStatus = pshr.idProjectStatusHistory')
            ->where('pshr.idProjectStatusHistory = :idProject')
            ->andWhere('prr.label = :rejectionReasonLabel')
            ->setParameter(':idProject', $project)
            ->setParameter(':rejectionReasonLabel', $rejectionReasonLabel)
            ->orderBy('psh.added', 'DESC')
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Projects|int $project
     * @param string       $abandonReasonLabel
     *
     * @return ProjectStatusHistoryReason|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findAbandonReasonByProjectAndLabel($project, string $abandonReasonLabel)
    {
        $queryBuilder = $this->createQueryBuilder('pshr');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:ProjectsStatusHistory', 'psh', Join::WITH, 'psh.idProjectStatus = pshr.idProjectStatusHistory')
            ->innerJoin('UnilendCoreBusinessBundle:ProjectAbandonReason', 'par', Join::WITH, 'par.idAbandon = pshr.idAbandonReason')
            ->where('pshr.idProjectStatusHistory = :idProject')
            ->andWhere('par.label = :abandonReasonLabel')
            ->setParameter(':idProject', $project)
            ->orderBy('psh.added', 'DESC')
            ->setMaxResults(1)
            ->setParameter(':abandonReasonLabel', $abandonReasonLabel);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
