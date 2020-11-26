<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Core\Entity\MarketSegment;

/**
 * @method MarketSegment|null find($id, $lockMode = null, $lockVersion = null)
 * @method MarketSegment|null findOneBy(array $criteria, array $orderBy = null)
 * @method MarketSegment[]    findAll()
 * @method MarketSegment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MarketSegmentRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, MarketSegment::class);
    }
}
