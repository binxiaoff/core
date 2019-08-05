<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\CompanyStatusHistory;

/**
 * @method CompanyStatusHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyStatusHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyStatusHistory[]    findAll()
 * @method CompanyStatusHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyStatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyStatusHistory::class);
    }
}
