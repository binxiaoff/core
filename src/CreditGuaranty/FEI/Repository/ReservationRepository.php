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
use KLS\CreditGuaranty\FEI\DTO\Query;
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

    public function findByReportingFilters(Program $program, Query $query, int $itemsPerPage, int $page): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('r');

        if (empty($query->getJoins())) {
            $queryBuilder->andWhere('1 = 0');
        } else {
            $queryBuilder
                ->select('financingObjects.id AS id_financing_object')
                ->leftJoin(
                    FinancingObject::class,
                    'financingObjects',
                    Join::WITH,
                    'r.id = financingObjects.reservation'
                )
            ;

            foreach ($query->getSelects() as $select) {
                $queryBuilder->addSelect($select);
            }
            foreach ($query->getJoins() as $join) {
                $queryBuilder->leftJoin(...$join);
            }

            $queryBuilder
                ->innerJoin('r.program', 'program')
                ->innerJoin('r.currentStatus', 'rcs')
                ->where('program = :program')
                ->andWhere('rcs.status = :reservationStatus')
                ->setParameter('program', $program)
                ->setParameter('reservationStatus', ReservationStatus::STATUS_CONTRACT_FORMALIZED)
            ;

            foreach ($query->getClauses() as $clause) {
                $queryBuilder->andWhere($clause['expression']);

                if (false === empty($clause['parameter'])) {
                    $queryBuilder->setParameter(...$clause['parameter']);
                }
            }

            foreach ($query->getOrders() as $orderBy => $orderDirection) {
                $queryBuilder->addOrderBy($orderBy, $orderDirection);
            }
        }

        $criteria = Criteria::create()
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage)
        ;
        $queryBuilder->addCriteria($criteria);

        $doctrinePaginator = new DoctrinePaginator($queryBuilder, false);
        $doctrinePaginator->setUseOutputWalkers(false);

        return new Paginator($doctrinePaginator);
    }
}
