<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\BlocsElements;

/**
 * @method BlocsElements|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlocsElements|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlocsElements[]    findAll()
 * @method BlocsElements[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlocsElementsRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlocsElements::class);
    }
}
