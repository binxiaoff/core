<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityCondition;

/**
 * @method ProgramEligibilityCondition|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProgramEligibilityCondition|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProgramEligibilityCondition[]    findAll()
 * @method ProgramEligibilityCondition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgramEligibilityConditionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, ProgramEligibilityCondition::class);
    }
}
