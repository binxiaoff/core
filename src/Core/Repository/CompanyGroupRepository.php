<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\CompanyGroup;

/**
 * @method CompanyGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyGroup[]    findAll()
 * @method CompanyGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyGroup::class);
    }
}
