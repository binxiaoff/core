<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\CreditGuaranty\Entity\StaffPermission;

/**
 * @method StaffPermission|null find($id, $lockMode = null, $lockVersion = null)
 * @method StaffPermission|null findOneBy(array $criteria, array $orderBy = null)
 * @method StaffPermission[]    findAll()
 * @method StaffPermission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StaffPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, StaffPermission::class);
    }
}
