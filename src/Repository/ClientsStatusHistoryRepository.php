<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\{ClientsStatusHistory};

/**
 * @method ClientsStatusHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientsStatusHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientsStatusHistory[]    findAll()
 * @method ClientsStatusHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientsStatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientsStatusHistory::class);
    }
}
