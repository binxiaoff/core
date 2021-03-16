<?php

declare(strict_types=1);

namespace Unilend\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Agency\Entity\Term;
use Unilend\Agency\Entity\TermHistory;

/**
 * @method TermHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method TermHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method TermHistory[]    findAll()
 * @method TermHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermHistoryRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, TermHistory::class);
    }

    /**
     * @param Term $term
     *
     * @return int|mixed|string|null
     *
     * @throws NonUniqueResultException
     */
    public function findLatestHistoryEntry(Term $term): ?TermHistory
    {
        return $this->createQueryBuilder('th')
            ->where('th.term = :term')
            ->orderBy('th.added', 'desc')
            ->setMaxResults(1)
            ->setParameter('term', $term)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
