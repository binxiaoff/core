<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\CompanySector;

class CompanySectorRepository extends ServiceEntityRepository
{
    /**
     * CompanySectorRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanySector::class);
    }
}
