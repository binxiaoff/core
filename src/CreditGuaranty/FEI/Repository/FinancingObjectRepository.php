<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use KLS\CreditGuaranty\FEI\Entity\FinancingObject;

/**
 * @method FinancingObject|null find($id, $lockMode = null, $lockVersion = null)
 * @method FinancingObject|null findOneBy(array $criteria, array $orderBy = null)
 * @method FinancingObject[]    findAll()
 * @method FinancingObject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FinancingObjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, FinancingObject::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * @throws MappingException
     */
    public function clear(): void
    {
        $this->getEntityManager()->clear();
    }
}
