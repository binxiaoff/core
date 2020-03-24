<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Unilend\Entity\CompanyModule;

/**
 * @method CompanyModule|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompanyModule|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompanyModule[]    findAll()
 * @method CompanyModule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyModuleRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, CompanyModule::class);
    }

    /**
     * @param CompanyModule $module
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(CompanyModule $module)
    {
        $this->getEntityManager()->persist($module);
        $this->getEntityManager()->flush();
    }
}
