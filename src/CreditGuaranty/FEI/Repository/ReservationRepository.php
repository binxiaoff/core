<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\Staff;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ProgramStatus;
use KLS\CreditGuaranty\FEI\Entity\Reservation;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;

/**
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Reservation::class);
    }

    /**
     * @throws ORMException
     */
    public function persist(Reservation $reservation): void
    {
        $this->getEntityManager()->persist($reservation);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function countByStaffAndProgramAndStatuses(Staff $staff, Program $program, array $statuses): int
    {
        $queryBuilder = $this->createQueryBuilder('r');

        return (int) $queryBuilder
            ->select('COUNT(r)')
            ->innerJoin('r.program', 'p')
            ->innerJoin('r.currentStatus', 'rs')
            ->innerJoin('p.currentStatus', 'ps')
            ->leftJoin('p.participations', 'pp')
            ->where('r.program = :program')
            ->andWhere($queryBuilder->expr()->orX(
                'p.managingCompany = :staffCompany',
                'pp.participant = :staffCompany AND ps.status <> :statusDraft'
            ))
            ->andWhere('rs.status IN (:statuses)')
            ->setParameter('program', $program)
            ->setParameter('staffCompany', $staff->getCompany())
            ->setParameter('statusDraft', ProgramStatus::STATUS_DRAFT)
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findByCurrentStatus(int $status): iterable
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.currentStatus', 'rs')
            ->where('rs.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByReportingFilters(
        Program $program,
        array $selects,
        array $joins,
        array $clauses,
        array $orders,
        int $itemsPerPage,
        int $page
    ): Paginator {
        $qb = $this->createQueryBuilder('r');

        if (empty($selects) || empty($joins)) {
            $qb->andWhere('1 = 0');
        } else {
            $qb
                ->select('financingObjects.id AS id_financing_object')
                ->addSelect('financingObjects.reportingFirstDate AS reporting_first_date')
                ->addSelect('financingObjects.reportingLastDate AS reporting_last_date')
                ->addSelect('financingObjects.reportingValidationDate AS reporting_validation_date')
                ->leftJoin(
                    FinancingObject::class,
                    'financingObjects',
                    Join::WITH,
                    'r.id = financingObjects.reservation'
                )
            ;

            foreach ($selects as $select) {
                $qb->addSelect($select);
            }
            foreach ($joins as $join) {
                $qb->leftJoin(...$join);
            }

            $qb
                ->innerJoin('r.program', 'program')
                ->innerJoin('r.currentStatus', 'rcs')
                ->where('program = :program')
                ->andWhere('rcs.status = :reservationStatus')
                ->setParameter('program', $program)
                ->setParameter('reservationStatus', ReservationStatus::STATUS_CONTRACT_FORMALIZED)
            ;

            foreach ($clauses as $clause) {
                $qb
                    ->andWhere($clause['expression'])
                    ->setParameter(...$clause['parameter'])
                ;
            }

            foreach ($orders as $orderBy => $orderDirection) {
                $qb->addOrderBy($orderBy, $orderDirection);
            }
        }

        $criteria = Criteria::create()
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
        ;
        $qb->addCriteria($criteria);

        $doctrinePaginator = new DoctrinePaginator($qb, false);
        $doctrinePaginator->setUseOutputWalkers(false);

        return new Paginator($doctrinePaginator);
    }
}
