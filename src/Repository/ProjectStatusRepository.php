<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\ProjectStatus;

/**
 * @method ProjectStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectStatus[]    findAll()
 * @method ProjectStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectStatusRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectStatus::class);
    }
}
