<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\ProjectEligibilityAssessment;
use Unilend\Entity\Projects;

class ProjectEligibilityAssessmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectEligibilityAssessment::class);
    }

    /**
     * @return Projects[]
     */
    public function getEvaluatedProjects()
    {
        $qb = $this->createQueryBuilder('pea');
        $qb->select('p')
            ->innerJoin(Projects::class, 'p', Join::WITH, 'p.idProject = pea.idProject')
            ->groupBy('pea.idProject');

        return $qb->getQuery()->getResult();
    }
}
