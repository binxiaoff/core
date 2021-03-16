<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\CreditGuaranty\Entity\ConstantList\EligibilityCriteria;

/**
 * @method EligibilityCriteria|null find($id, $lockMode = null, $lockVersion = null)
 * @method EligibilityCriteria|null findOneBy(array $criteria, array $orderBy = null)
 * @method EligibilityCriteria[]    findAll()
 * @method EligibilityCriteria[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EligibilityCriteriaRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, EligibilityCriteria::class);
    }
}
