<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatusHistory;

class ProjectsStatusHistoryRepository extends EntityRepository
{
    /**
     * @param int|Projects       $project
     * @param int|ProjectsStatus $status
     *
     * @return ProjectsStatusHistory|null
     */
    public function findStatusFirstOccurrence($project, $status)
    {
        if ($project instanceof Projects) {
            $project = $project->getIdProject();
        }
        if ($status instanceof ProjectsStatus) {
            $status = $status->getStatus();
        }
        $qb = $this->createQueryBuilder('psh');
        $qb->innerJoin('UnilendCoreBusinessBundle:ProjectsStatus', 'ps', Join::WITH, 'ps.idProjectStatus = psh.idProjectStatus')
           ->andWhere('psh.idProject = :project')
           ->andWhere('ps.status = :status')
           ->setParameter('project', $project)
           ->setParameter('status', $status)
           ->orderBy('psh.added', 'ASC')
           ->setMaxResults(1);
        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param int $projectId
     * @param int $projectStatus
     *
     * @return array
     */
    public function getHistoryAfterGivenStatus($projectId, $projectStatus)
    {
        $queryBuilder = $this->createQueryBuilder('psh');
        $queryBuilder->select('psh.idProject, psh.idProjectStatusHistory, ps.status, ps.label, pshd.siteContent, psh.added')
            ->innerJoin('UnilendCoreBusinessBundle:ProjectsStatus', 'ps', Join::WITH, 'ps.idProjectStatus = psh.idProjectStatus')
            ->leftJoin('UnilendCoreBusinessBundle:ProjectsStatusHistoryDetails', 'pshd', Join::WITH, 'psh.idProjectStatusHistory = pshd.idProjectStatusHistory')
            ->where('psh.idProject = :projectId')
            ->andWhere('ps.status > :status')
            ->setParameters(['projectId' => $projectId, 'status' => $projectStatus])
            ->orderBy('psh.added', 'DESC');

        return $queryBuilder->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }

    /**
     * @param DateTime $dateAdded
     * @param array    $projectStatus
     *
     * @return array|bool
     */
    public function getProjectStatusChangesOnDate(\DateTime $dateAdded, $projectStatus)
    {
        if (empty($dateAdded)) {
            return false;
        }

        if (empty($projectStatus) || false === is_array($projectStatus)) {
            return false;
        }

        $qb = $this->createQueryBuilder('psh');
        $qb->innerJoin('UnilendCoreBusinessBundle:ProjectsStatus', 'ps', Join::WITH, 'ps.idProjectStatus = psh.idProjectStatus')
            ->andWhere('DATE(psh.added) = :date')
            ->andWhere('ps.status IN (:status)')
            ->setParameter(':date', $dateAdded->format('Y-m-d'))
            ->setParameter(':status', $projectStatus,Connection::PARAM_INT_ARRAY);
        $query = $qb->getQuery();

        return $query->getResult();
    }
}
