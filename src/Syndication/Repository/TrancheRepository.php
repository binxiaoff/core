<?php

declare(strict_types=1);

namespace Unilend\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Syndication\Entity\Tranche;

/**
 * @method Tranche|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tranche|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tranche[]    findAll()
 * @method Tranche[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrancheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Tranche::class);
    }
}
