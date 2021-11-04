<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\CreditGuaranty\FEI\Entity\ProgramEligibilityConfiguration;

/**
 * @method ProgramEligibilityConfiguration|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProgramEligibilityConfiguration|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProgramEligibilityConfiguration[]    findAll()
 * @method ProgramEligibilityConfiguration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgramEligibilityConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, ProgramEligibilityConfiguration::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProgramEligibilityConfiguration $programEligibilityConfiguration): void
    {
        $this->getEntityManager()->persist($programEligibilityConfiguration);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws ORMException
     */
    public function remove(ProgramEligibilityConfiguration $programEligibilityConfiguration): void
    {
        $this->getEntityManager()->remove($programEligibilityConfiguration);
        $this->getEntityManager()->flush();
    }
}
