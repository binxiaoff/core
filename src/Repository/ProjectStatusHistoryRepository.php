<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\ProjectStatusHistory;

/**
 * @method ProjectStatusHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectStatusHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectStatusHistory[]    findAll()
 * @method ProjectStatusHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectStatusHistoryRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectStatusHistory::class);
    }
}
