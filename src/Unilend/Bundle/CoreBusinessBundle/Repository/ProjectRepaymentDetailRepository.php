<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentDetail;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog;

class ProjectRepaymentDetailRepository extends EntityRepository
{

    /**
     * @param ProjectRepaymentTaskLog|int $projectRepaymentTaskLog
     * @param int                         $limit
     *
     * @return ProjectRepaymentDetail[]
     */
    public function findRandomlyUncompletedByTaskExecutionForCapital($projectRepaymentTaskLog, $limit)
    {
        $queryBuilder = $this->createQueryBuilder('prd');
        $queryBuilder->where('prd.idTaskLog = :taskLog')
            ->andWhere('prd.capitalCompleted = :capitalUncompleted')
            ->orderBy('RAND()')
            ->setParameter('taskLog', $projectRepaymentTaskLog)
            ->setParameter('capitalUncompleted', ProjectRepaymentDetail::CAPITAL_UNCOMPLETED)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param ProjectRepaymentTaskLog|int $projectRepaymentTaskLog
     * @param int                         $limit
     *
     * @return ProjectRepaymentDetail[]
     */
    public function findRandomlyUncompletedByTaskExecutionForInterest($projectRepaymentTaskLog, $limit)
    {
        $queryBuilder = $this->createQueryBuilder('prd');
        $queryBuilder->where('prd.idTaskLog = :taskLog')
            ->andWhere('prd.interestCompleted = :capitalUncompleted')
            ->orderBy('RAND()')
            ->setParameter('taskLog', $projectRepaymentTaskLog)
            ->setParameter('capitalUncompleted', ProjectRepaymentDetail::INTEREST_UNCOMPLETED)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }
}
