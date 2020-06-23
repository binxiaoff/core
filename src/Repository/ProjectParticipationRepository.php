<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use Unilend\Entity\{Clients, Project, ProjectParticipation, Staff};

/**
 * @method ProjectParticipation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipation[]    findAll()
 * @method ProjectParticipation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipation::class);
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectParticipation $projectParticipation): void
    {
        $this->getEntityManager()->persist($projectParticipation);
        $this->getEntityManager()->flush();
    }
}
