<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Syndication\Agency\Entity\Term;

/**
 * @method Term|null find($id, $lockMode = null, $lockVersion = null)
 * @method Term|null findOneBy(array $criteria, array $orderBy = null)
 * @method Term[]    findAll()
 * @method Term[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Term::class);
    }

    /**
     * @return iterable|Term[]
     */
    public function findByProject(Project $project): iterable
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.covenant', 'c')
            ->where('c.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return iterable|Term[]
     */
    public function findSharedByProject(Project $project): iterable
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.covenant', 'c')
            ->where('c.project = :project')
            ->andWhere('t.sharingDate IS NOT NULL')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return array|Term[]
     */
    public function findUnsharedInPublishedProjectStartingToday(): array
    {
        return $this->getUnsharedInPublishedProjectQueryBuilder()
            ->andWhere('t.startDate = :day')
            ->setParameter('day', (new \DateTimeImmutable('today'))->format('Y-m-d'))
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array|Term[]
     */
    public function findUnsharedInPublishedProjectEndingTomorrow(): array
    {
        return $this->getUnsharedInPublishedProjectQueryBuilder()
            ->andWhere('t.endDate = :day')
            ->setParameter('day', (new \DateTimeImmutable('+ 1 day'))->format('Y-m-d'))
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array|Term[]
     */
    public function findUnsharedInPublishedProjectEndingNextWeek(): array
    {
        return $this->getUnsharedInPublishedProjectQueryBuilder()
            ->andWhere('t.endDate = :day')
            ->setParameter('day', (new \DateTimeImmutable('+ 1 week'))->format('Y-m-d'))
            ->getQuery()
            ->getResult()
            ;
    }

    private function getUnsharedInPublishedProjectQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.covenant', 'c')
            ->innerJoin('c.project', 'p')
            ->where('p.currentStatus = :publishedStatus')
            ->andWhere('t.sharingDate IS NULL')
            ->setParameter('publishedStatus', Project::STATUS_PUBLISHED)
        ;
    }
}
