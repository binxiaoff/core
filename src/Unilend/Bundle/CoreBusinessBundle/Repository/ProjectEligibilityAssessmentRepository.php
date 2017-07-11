<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

class ProjectEligibilityAssessmentRepository extends EntityRepository
{
    /**
     * @return Projects[]
     */
    public function getEvaluatedProjects()
    {
        $qb = $this->createQueryBuilder('pea');
        $qb->select('p')
            ->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH, 'p.idProject = pea.idProject')
            ->groupBy('pea.idProject');

        return $qb->getQuery()->getResult();
    }
}
