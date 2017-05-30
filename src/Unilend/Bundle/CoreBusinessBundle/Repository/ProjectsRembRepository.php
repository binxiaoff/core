<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsRemb;

class ProjectsRembRepository extends EntityRepository
{
    /**
     * @param \DateTime $repaymentDate
     * @param int       $limit
     *
     * @return ProjectsRemb[]
     */
    public function getProjectsToRepay(\DateTime $repaymentDate, $limit)
    {
        $qb = $this->createQueryBuilder('pr');
        $qb->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH, 'pr.idProject = p.idProject')
            ->where('p.rembAuto = :on')
            ->andWhere('pr.status = :pending')
            ->andWhere('DATE(pr.dateRembPreteurs) <= :repaymentDate')
            ->setParameter('on', Projects::AUTO_REPAYMENT_ON)
            ->setParameter('pending', ProjectsRemb::STATUS_PENDING)
            ->setParameter('repaymentDate', $repaymentDate)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
