<?php

declare(strict_types=1);

namespace Unilend\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Entity\Term;

/**
 * @method Term|null find($id, $lockMode = null, $lockVersion = null)
 * @method Term|null findOneBy(array $criteria, array $orderBy = null)
 * @method Term[]    findAll()
 * @method Term[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Term::class);
    }

    /**
     * @param Project $project
     *
     * @return iterable|Term[]
     */
    public function findByProject(Project $project): iterable
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.covenant', 'c')
            ->where('c.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Project $project
     *
     * @return iterable|Term[]
     */
    public function findActiveByProject(Project $project): iterable
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.covenant', 'c')
            ->where('c.project = :project')
            ->andWhere('t.archivingDate IS NULL')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Project $project
     *
     * @return iterable|Term[]
     */
    public function findArchivedByProject(Project $project): iterable
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.covenant', 'c')
            ->where('c.project = :project')
            ->andWhere('t.archivingDate IS NOT NULL')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult();
    }
}
