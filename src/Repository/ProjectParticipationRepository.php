<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\ProjectParticipation;

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
}
