<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{ProjectAbandonReason, ProjectRejectionReason, Projects, ProjectsStatusHistory, ProjectStatusHistoryReason};

class ProjectStatusHistoryReasonRepository extends EntityRepository
{
    /**
     * @param Projects|int $project
     * @param string       $rejectionReasonLabel
     *
     * @return ProjectStatusHistoryReason|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastRejectionReasonByProjectAndLabel($project, string $rejectionReasonLabel): ?ProjectStatusHistoryReason
    {
        $queryBuilder = $this->createQueryBuilder('pshr');
        $queryBuilder
            ->innerJoin(ProjectRejectionReason::class, 'reason', Join::WITH, 'reason.idRejection = pshr.idRejectionReason')
            ->innerJoin(ProjectsStatusHistory::class, 'psh', Join::WITH, 'psh.idProjectStatusHistory = pshr.idProjectStatusHistory')
            ->where('psh.idProject = :idProject')
            ->setParameter('idProject', $project)
            ->andWhere('reason.label = :rejectionReasonLabel')
            ->setParameter('rejectionReasonLabel', $rejectionReasonLabel)
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
    public function findLastAbandonReasonByProjectAndLabel($project, string $abandonReasonLabel): ?ProjectStatusHistoryReason
    {
        $queryBuilder = $this->createQueryBuilder('pshr');
        $queryBuilder
            ->innerJoin(ProjectAbandonReason::class, 'reason', Join::WITH, 'reason.idAbandon = pshr.idAbandonReason')
            ->innerJoin(ProjectsStatusHistory::class, 'psh', Join::WITH, 'psh.idProjectStatusHistory = pshr.idProjectStatusHistory')
            ->where('psh.idProject = :idProject')
            ->setParameter('idProject', $project)
            ->andWhere('reason.label = :abandonReasonLabel')
            ->setParameter('abandonReasonLabel', $abandonReasonLabel)
            ->orderBy('psh.added', 'DESC')
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
