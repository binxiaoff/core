<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\CreditGuaranty\FEI\Entity\ProgramBorrowerTypeAllocation;

/**
 * @method ProgramBorrowerTypeAllocation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProgramBorrowerTypeAllocation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProgramBorrowerTypeAllocation[]    findAll()
 * @method ProgramBorrowerTypeAllocation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgramBorrowerTypeAllocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, ProgramBorrowerTypeAllocation::class);
    }

    /**
     * @throws ORMException
     */
    public function remove(ProgramBorrowerTypeAllocation $programBorrowerTypeAllocation): void
    {
        $this->getEntityManager()->remove($programBorrowerTypeAllocation);
        $this->getEntityManager()->flush();
    }
}
