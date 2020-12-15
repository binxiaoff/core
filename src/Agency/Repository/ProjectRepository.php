<?php

declare(strict_types=1);

namespace Unilend\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Agency\Entity\Project;

/**
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
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
}
