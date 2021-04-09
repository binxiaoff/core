<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\CreditGuaranty\Entity\ProgramEligibilityConfiguration;

/**
 * @method ProgramEligibilityConfiguration|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProgramEligibilityConfiguration|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProgramEligibilityConfiguration[]    findAll()
 * @method ProgramEligibilityConfiguration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgramEligibilityConfigurationRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, ProgramEligibilityConfiguration::class);
    }
}
