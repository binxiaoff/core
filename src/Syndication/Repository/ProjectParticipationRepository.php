<?php

declare(strict_types=1);

namespace KLS\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Syndication\Entity\ProjectParticipation;

/**
 * @method ProjectParticipation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipation[]    findAll()
 * @method ProjectParticipation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipation::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectParticipation $projectParticipation): void
    {
        $this->persist($projectParticipation);
        $this->flush();
    }

    /**
     * @throws ORMException
     */
    public function persist(ProjectParticipation $projectParticipation): void
    {
        $this->getEntityManager()->persist($projectParticipation);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
