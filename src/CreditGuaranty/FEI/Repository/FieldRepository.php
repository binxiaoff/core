<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KLS\CreditGuaranty\FEI\Entity\Field;

/**
 * @method Field|null find($id, $lockMode = null, $lockVersion = null)
 * @method Field|null findOneBy(array $criteria, array $orderBy = null)
 * @method Field[]    findAll()
 * @method Field[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Field::class);
    }
}
