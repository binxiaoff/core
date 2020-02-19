<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\TrancheAttribute;

/**
 * @method TrancheAttribute|null find($id, $lockMode = null, $lockVersion = null)
 * @method TrancheAttribute|null findOneBy(array $criteria, array $orderBy = null)
 * @method TrancheAttribute[]    findAll()
 * @method TrancheAttribute[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrancheAttributeRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrancheAttribute::class);
    }
}
