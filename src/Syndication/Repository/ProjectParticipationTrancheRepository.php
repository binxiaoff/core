<?php

declare(strict_types=1);

namespace Unilend\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Syndication\Entity\ProjectParticipationTranche;

/**
 * @method ProjectParticipationTranche|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipationTranche|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipationTranche[]    findAll()
 * @method ProjectParticipationTranche[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationTrancheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipationTranche::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectParticipationTranche $projectParticipationTranche): void
    {
        $this->getEntityManager()->persist($projectParticipationTranche);
        $this->getEntityManager()->flush();
    }
}
