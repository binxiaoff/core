<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\CreditGuaranty\Entity\FieldConfiguration;

/**
 * @method FieldConfiguration|null find($id, $lockMode = null, $lockVersion = null)
 * @method FieldConfiguration|null findOneBy(array $criteria, array $orderBy = null)
 * @method FieldConfiguration[]    findAll()
 * @method FieldConfiguration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FieldConfigurationRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, FieldConfiguration::class);
    }
}
