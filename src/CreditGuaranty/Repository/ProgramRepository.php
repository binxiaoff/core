<?php

declare(strict_types=1);

namespace KLS\CreditGuaranty\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\CreditGuaranty\Entity\Program;

/**
 * @method Program|null find($id, $lockMode = null, $lockVersion = null)
 * @method Program|null findOneBy(array $criteria, array $orderBy = null)
 * @method Program[]    findAll()
 * @method Program[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Program::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Program $program): void
    {
        $this->getEntityManager()->persist($program);
        $this->getEntityManager()->flush();
    }
}
