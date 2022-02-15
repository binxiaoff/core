<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\FEI\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\CreditGuaranty\FEI\Entity\Reporting;

/**
 * @method Reporting|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reporting|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reporting[]    findAll()
 * @method Reporting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Reporting::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Reporting $reporting): void
    {
        $this->getEntityManager()->persist($reporting);
        $this->getEntityManager()->flush();
    }
}
