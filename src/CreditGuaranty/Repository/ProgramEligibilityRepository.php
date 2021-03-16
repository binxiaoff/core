<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;

/**
 * @method ProgramEligibility|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProgramEligibility|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProgramEligibility[]    findAll()
 * @method ProgramEligibility[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgramEligibilityRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, ProgramEligibility::class);
    }
}
