<?php

declare(strict_types=1);

namespace KLS\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Syndication\Entity\ProjectParticipationStatus;

/**
 * @method ProjectParticipationStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipationStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipationStatus[]    findAll()
 * @method ProjectParticipationStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipationStatus::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectParticipationStatus $ProjectParticipationStatus): void
    {
        $this->getEntityManager()->persist($ProjectParticipationStatus);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws ORMException
     */
    public function persist(ProjectParticipationStatus $ProjectParticipationStatus): void
    {
        $this->getEntityManager()->persist($ProjectParticipationStatus);
    }
}
