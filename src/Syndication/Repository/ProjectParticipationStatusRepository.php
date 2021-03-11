<?php

declare(strict_types=1);

namespace Unilend\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Syndication\Entity\ProjectParticipationStatus;

/**
 * @method ProjectParticipationStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipationStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipationStatus[]    findAll()
 * @method ProjectParticipationStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationStatusRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipationStatus::class);
    }

    /**
     * @param ProjectParticipationStatus $ProjectParticipationStatus
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectParticipationStatus $ProjectParticipationStatus): void
    {
        $this->getEntityManager()->persist($ProjectParticipationStatus);
        $this->getEntityManager()->flush();
    }

    /**
     * @param ProjectParticipationStatus $ProjectParticipationStatus
     *
     * @throws ORMException
     */
    public function persist(ProjectParticipationStatus $ProjectParticipationStatus): void
    {
        $this->getEntityManager()->persist($ProjectParticipationStatus);
    }
}
