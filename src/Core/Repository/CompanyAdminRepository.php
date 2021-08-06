<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\CompanyAdmin;

/**
 * @method CompanyAdmin|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyAdmin|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyAdmin[]    findAll()
 * @method CompanyAdmin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyAdminRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyAdmin::class);
    }
}
