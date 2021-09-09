<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\Company;
use KLS\Core\Repository\Traits\OrderByHandlerTrait;
use KLS\Core\Repository\Traits\PaginationHandlerTrait;
use KLS\Syndication\Arrangement\Entity\Project;

/**
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    use OrderByHandlerTrait;
    use PaginationHandlerTrait;

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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countProjectParticipationMembers(Project $project): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(ppc)')
            ->innerJoin('p.projectParticipations', 'pp')
            ->innerJoin('pp.projectParticipationMembers', 'ppc')
            ->where('p = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function countProjectsByCompany(Company $company)
    {
        return $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->where('p.submitterCompany = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getSingleScalarResult()
            ;
    }
}
