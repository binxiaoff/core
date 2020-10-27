<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Entity\ProjectParticipationTrancheVersion;

/**
 * @method ProjectParticipationTrancheVersion|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipationTrancheVersion|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipationTrancheVersion[]    findAll()
 * @method ProjectParticipationTrancheVersion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationTrancheVersionRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipationTrancheVersion::class);
    }
}
