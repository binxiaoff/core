<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
