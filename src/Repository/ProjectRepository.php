<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{NoResultException, NonUniqueResultException, ORMException, OptimisticLockException};
use Unilend\Entity\Project;
use Unilend\Repository\Traits\{OrderByHandlerTrait, PaginationHandlerTrait};

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

    /**
     * ProjectRepository constructor.
     *
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Project::class);
    }

    /**
     * @param Project $project
     *
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
     * @param Project $project
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @return int
     */
    public function countProjectParticipationContact(Project $project): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(ppc)')
            ->innerJoin('p.projectParticipations', 'pp')
            ->innerJoin('pp.projectParticipationContacts', 'ppc')
            ->where('p = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
