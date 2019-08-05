<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\InseePays;

/**
 * @method InseePays|null find($id, $lockMode = null, $lockVersion = null)
 * @method InseePays|null findOneBy(array $criteria, array $orderBy = null)
 * @method InseePays[]    findAll()
 * @method InseePays[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InseePaysRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InseePays::class);
    }
}
