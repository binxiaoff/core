<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{Loans, ProjectRepaymentDetail, ProjectRepaymentTask};

class ProjectRepaymentDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectRepaymentDetail::class);
    }


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
     * @param Loans|int $loan
     * @param int|null  $sequence
     *
     * @return array
     */
    public function getPendingAmountToRepay($loan, $sequence = null)
    {
        $queryBuilder = $this->createQueryBuilder('prd');
        $queryBuilder->select('SUM(prd.capital) as capital, SUM(prd.interest) as interest')
            ->innerJoin(ProjectRepaymentTask::class, 'prt', Join::WITH, 'prt.id = prd.idTask')
            ->where('prd.idLoan = :loan')
            ->andWhere('prd.status = :pending')
            ->groupBy('prd.idLoan')
            ->setParameter('loan', $loan)
            ->setParameter(':pending', ProjectRepaymentDetail::STATUS_PENDING);

        if (is_numeric($sequence)) {
            $queryBuilder->andWhere('prt.sequence = :sequence')
                ->setParameter(':sequence', $sequence);
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return int
     */
    public function deleteFinished(\DateTime $dateTime)
    {
        $delete = 'DELETE FROM project_repayment_detail WHERE status = :finished AND updated < :someTime';

        return $this->getEntityManager()->getConnection()->executeUpdate($delete, ['finished' => ProjectRepaymentDetail::STATUS_NOTIFIED, 'someTime' => $dateTime->format('Y-m-d H:i:s')]);
    }
}
