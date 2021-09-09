<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Team;
use KLS\Core\Entity\TeamEdge;
use KLS\Syndication\Agency\Entity\Project;

/**
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Project::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Project $project): void
    {
        $this->getEntityManager()->persist($project);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countProjectsByCompany(Company $company)
    {
        return $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->innerJoin(Staff::class, 's', Join::WITH, 'p.addedBy = s.id')
            ->innerJoin(Team::class, 't', Join::WITH, 's.team = t.id')
            ->leftJoin(TeamEdge::class, 'te', Join::WITH, 's.team = te.descendent')
            ->innerJoin(Company::class, 'c', Join::WITH, 's.team = c.rootTeam OR te.ancestor = c.rootTeam')
            ->where('c = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }
}
