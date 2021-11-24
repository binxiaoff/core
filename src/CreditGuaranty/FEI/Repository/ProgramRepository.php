<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\Embeddable\NullableMoney;
use KLS\CreditGuaranty\FEI\Entity\Program;

/**
 * @method Program|null find($id, $lockMode = null, $lockVersion = null)
 * @method Program|null findOneBy(array $criteria, array $orderBy = null)
 * @method Program[]    findAll()
 * @method Program[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Program::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Program $program): void
    {
        $this->getEntityManager()->persist($program);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countReservations(Program $program, array $reservationStatuses): int
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select('COUNT(r)')
            ->innerJoin('p.reservations', 'r')
            ->innerJoin('r.currentStatus', 'rs')
            ->where('p = :program')
            ->andWhere('rs.status IN (:statuses)')
            ->setParameter('program', $program)
            ->setParameter('statuses', $reservationStatuses)
        ;

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function sumProjectsAmounts(Program $program, array $reservationStatus): NullableMoney
    {
        $queryBuilder = $this->createQueryBuilder('program')
            ->select('SUM(project.fundingMoney.amount) AS amount, project.fundingMoney.currency AS currency')
            ->innerJoin('program.reservations', 'r')
            ->innerJoin('r.project', 'project')
            ->innerJoin('r.currentStatus', 'cs')
            ->where('program = :program')
            ->andWhere('cs.status in (:status)')
            ->groupBy('project.fundingMoney.currency')
            ->setParameter('program', $program)
            ->setParameter('status', $reservationStatus)
        ;

        $sum = $queryBuilder->getQuery()->getScalarResult();

        return isset($sum[0]['amount'], $sum[0]['currency']) ?
            new NullableMoney($sum[0]['currency'], $sum[0]['amount'])
            : new NullableMoney(null, null);
    }
}
