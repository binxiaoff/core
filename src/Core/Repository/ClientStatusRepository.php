<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Core\Entity\ClientStatus;

/**
 * @method ClientStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientStatus[]    findAll()
 * @method ClientStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientStatusRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientStatus::class);
    }
}
