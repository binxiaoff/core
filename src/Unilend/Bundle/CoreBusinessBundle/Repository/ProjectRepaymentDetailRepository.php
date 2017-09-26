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
    public function findRandomlyUncompletedByTaskExecutionForCapital($projectRepaymentTask, $limit)
    {
        $queryBuilder = $this->createQueryBuilder('prd');
        $queryBuilder->where('prd.idTask = :task')
            ->andWhere('prd.capitalCompleted = :capitalUncompleted')
            ->orderBy('RAND()')
            ->setParameter('task', $projectRepaymentTask)
            ->setParameter('capitalUncompleted', ProjectRepaymentDetail::CAPITAL_UNCOMPLETED)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param ProjectRepaymentTask|int $projectRepaymentTask
     * @param int                      $limit
     *
     * @return ProjectRepaymentDetail[]
     */
    public function findRandomlyUncompletedByTaskExecutionForInterest($projectRepaymentTask, $limit)
    {
        $queryBuilder = $this->createQueryBuilder('prd');
        $queryBuilder->where('prd.idTask = :task')
            ->andWhere('prd.interestCompleted = :capitalUncompleted')
            ->orderBy('RAND()')
            ->setParameter('task', $projectRepaymentTask)
            ->setParameter('capitalUncompleted', ProjectRepaymentDetail::INTEREST_UNCOMPLETED)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param ProjectRepaymentTask|int $projectRepaymentTask
     *
     * @return float
     */
    public function getTotalCapitalToRepay($projectRepaymentTask)
    {
        $queryBuilder = $this->createQueryBuilder('prd');
        $queryBuilder->select('SUM(prd.capital)')
            ->where('prd.idTask = :task')
            ->setParameter('task', $projectRepaymentTask);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param ProjectRepaymentTask|int $projectRepaymentTask
     *
     * @return float
     */
    public function getTotalInterestToRepay($projectRepaymentTask)
    {
        $queryBuilder = $this->createQueryBuilder('prd');
        $queryBuilder->select('SUM(prd.interest)')
            ->where('prd.idTask = :task')
            ->setParameter('task', $projectRepaymentTask);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return int
     */
    public function deleteFinishedDetails(\DateTime $dateTime)
    {
        $delete = 'DELETE FROM project_repayment_detail WHERE status = :finished AND updated < :someTime';

        return $this->getEntityManager()->getConnection()->executeUpdate($delete, ['finished' => ProjectRepaymentDetail::STATUS_NOTIFIED, 'someTime' => $dateTime->format('Y-m-d H:i:s')]);
    }
}
