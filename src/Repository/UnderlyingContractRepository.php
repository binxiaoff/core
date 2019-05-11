<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\UnderlyingContract;

/**
 * @method UnderlyingContract|null find($id, $lockMode = null, $lockVersion = null)
 * @method UnderlyingContract|null findOneBy(array $criteria, array $orderBy = null)
 * @method UnderlyingContract[]    findAll()
 * @method UnderlyingContract[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UnderlyingContractRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnderlyingContract::class);
    }
}
