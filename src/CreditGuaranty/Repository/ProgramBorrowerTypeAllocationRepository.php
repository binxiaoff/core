<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\CreditGuaranty\Entity\ProgramBorrowerTypeAllocation;

/**
 * @method ProgramBorrowerTypeAllocation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProgramBorrowerTypeAllocation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProgramBorrowerTypeAllocation[]    findAll()
 * @method ProgramBorrowerTypeAllocation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgramBorrowerTypeAllocationRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, ProgramBorrowerTypeAllocation::class);
    }
}
