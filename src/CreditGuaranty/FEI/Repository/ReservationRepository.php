<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\CreditGuaranty\FEI\Entity\Program;
use KLS\CreditGuaranty\FEI\Entity\Reservation;

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

    public function findIdsByDuplicatedName(Program $program): array
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->select('r.name')
            ->addSelect('COUNT(r)')
            ->addSelect('GROUP_CONCAT(r.id) as ids')
            ->innerJoin('r.borrower', 'b')
            ->where('r.program = :program')
            ->setParameter('program', $program)
            ->groupBy('r.name')
            ->having('COUNT(r) > 1')
        ;

        $result = $queryBuilder->getQuery()->getResult();
        $result = \array_column($result, 'ids');
        \array_walk($result, static fn (&$value) => $value = \explode(',', $value));

        return $result;
    }
}
