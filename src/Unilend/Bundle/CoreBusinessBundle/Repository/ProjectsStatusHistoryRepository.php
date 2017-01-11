<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
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
}
