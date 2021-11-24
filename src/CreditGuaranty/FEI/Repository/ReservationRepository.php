<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use KLS\CreditGuaranty\FEI\DTO\Query;
use KLS\Core\Repository\Traits\QueryHandlerTrait;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Program;
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
    use QueryHandlerTrait;

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
        Query $query,
        ?int $offset = null,
        ?int $limit = null
    ): array {
        $queryBuilder = $this->buildQuery(
            $this->getReportingQueryBuilder($program),
            $query,
            $offset,
            $limit
        );

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function getPaginatorByReportingFilters(
        Program $program,
        Query $query,
        int $offset,
        int $limit
    ): Paginator {
        $queryBuilder = $this->buildQuery(
            $this->getReportingQueryBuilder($program),
            $query,
            $offset,
            $limit
        );

        $doctrinePaginator = new DoctrinePaginator($queryBuilder, false);
        $doctrinePaginator->setUseOutputWalkers(false);

        return new Paginator($doctrinePaginator);
    }

    private function getReportingQueryBuilder(Program $program): QueryBuilder
    {
        return $this->createQueryBuilder('r')
            ->select('financingObjects.id AS id_financing_object')
            ->innerJoin('r.program', 'program')
            ->innerJoin('r.currentStatus', 'rcs')
            ->leftJoin(
                FinancingObject::class,
                'financingObjects',
                Join::WITH,
                'r.id = financingObjects.reservation'
            )
            ->where('program = :program')
            ->andWhere('rcs.status = :reservationStatus')
            ->setParameter('program', $program)
            ->setParameter('reservationStatus', ReservationStatus::STATUS_CONTRACT_FORMALIZED)
        ;
    }
}
