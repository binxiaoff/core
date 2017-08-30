<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentDetail;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;

class ProjectRepaymentDetailRepository extends EntityRepository
{

    /**
     * @param ProjectRepaymentTask|int $projectRepaymentTask
     * @param int                      $limit
     *
     * @return ProjectRepaymentDetail[]
     */
    public function findRandomlyByTask($projectRepaymentTask, $limit)
    {
        $queryBuilder = $this->createQueryBuilder('prd');
        $queryBuilder->where('prd.idTask = :task')
            ->andWhere('prd.capitalCompleted = :capitalUncompleted or prd.interestCompleted = :interestUncompleted')
            ->orderBy('RAND()')
            ->setParameter('task', $projectRepaymentTask)
            ->setParameter('capitalUncompleted', ProjectRepaymentDetail::CAPITAL_UNCOMPLETED)
            ->setParameter('interestUncompleted', ProjectRepaymentDetail::INTEREST_UNCOMPLETED)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }
}
