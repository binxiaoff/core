<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{DebtCollectionMission, DebtCollectionMissionPaymentSchedule, Projects};

class DebtCollectionMissionPaymentScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DebtCollectionMissionPaymentSchedule::class);
    }

    /**
     * @param int|Projects $project
     *
     * @return mixed
     */
    public function getEntrustedAmount($project)
    {
        $queryBuilder = $this->createQueryBuilder('dcmps')
            ->select('SUM(dcmps.capital + dcmps.interest + dcmps.commissionVatIncl) AS entrustedAmount')
            ->innerJoin(DebtCollectionMission::class, 'dcm', Join::WITH, 'dcmps.idMission = dcm.id')
            ->where('dcm.idProject = :project')
            ->setParameter('project', $project)
            ->andWhere('dcm.archived IS NULL');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
