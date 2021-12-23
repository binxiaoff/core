<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Repository;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\DTO\Query;
use KLS\Core\Repository\Traits\QueryHandlerTrait;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\ReservationStatus;

/**
 * @method FinancingObject|null find($id, $lockMode = null, $lockVersion = null)
 * @method FinancingObject|null findOneBy(array $criteria, array $orderBy = null)
 * @method FinancingObject[]    findAll()
 * @method FinancingObject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FinancingObjectRepository extends ServiceEntityRepository
{
    use QueryHandlerTrait;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, FinancingObject::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function bulkUpdate(array $ids, array $data): void
    {
        if (empty($ids) || empty($data)) {
            return;
        }

        $queryBuilder = $this->createQueryBuilder('fo')
            ->update()
            ->where('fo.id IN (:ids)')
            ->setParameter('ids', $ids)
        ;

        foreach ($data as $property => $value) {
            $queryBuilder
                ->set('fo.' . $property, ':' . $property)
                ->setParameter($property, $value)
            ;
        }

        $queryBuilder->getQuery()->execute();
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
        return $this->createQueryBuilder('financingObjects')
            ->select('financingObjects.id AS id_financing_object')
            ->innerJoin('financingObjects.reservation', 'r')
            ->innerJoin('r.program', 'program')
            ->innerJoin('r.currentStatus', 'rcs')
            ->where('program = :program')
            ->andWhere('rcs.status = :reservationStatus')
            ->setParameter('program', $program)
            ->setParameter('reservationStatus', ReservationStatus::STATUS_CONTRACT_FORMALIZED)
        ;
    }
}
