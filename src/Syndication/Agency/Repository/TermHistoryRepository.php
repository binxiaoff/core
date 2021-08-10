<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Syndication\Agency\Entity\Term;
use KLS\Syndication\Agency\Entity\TermHistory;

/**
 * @method TermHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method TermHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method TermHistory[]    findAll()
 * @method TermHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, TermHistory::class);
    }

    /**
     * @throws NonUniqueResultException
     *
     * @return int|mixed|string|null
     */
    public function findLatestHistoryEntry(Term $term): ?TermHistory
    {
        return $this->createQueryBuilder('th')
            ->where('th.term = :term')
            ->orderBy('th.added', 'desc')
            ->setMaxResults(1)
            ->setParameter('term', $term)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
