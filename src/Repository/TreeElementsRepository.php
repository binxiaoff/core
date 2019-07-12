<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\TreeElements;

/**
 * @method TreeElements|null find($id, $lockMode = null, $lockVersion = null)
 * @method TreeElements|null findOneBy(array $criteria, array $orderBy = null)
 * @method TreeElements[]    findAll()
 * @method TreeElements[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TreeElementsRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TreeElements::class);
    }
}
