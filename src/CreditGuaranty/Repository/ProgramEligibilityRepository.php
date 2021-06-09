<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\CreditGuaranty\Entity\Program;
use Unilend\CreditGuaranty\Entity\ProgramEligibility;

/**
 * @method ProgramEligibility|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProgramEligibility|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProgramEligibility[]    findAll()
 * @method ProgramEligibility[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgramEligibilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, ProgramEligibility::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProgramEligibility $programEligibility): void
    {
        $this->getEntityManager()->persist($programEligibility);
        $this->getEntityManager()->flush();
    }

    public function findFieldCategoriesByProgram(Program $program): array
    {
        $categories = $this->createQueryBuilder('pe')
            ->select('DISTINCT f.category')
            ->innerJoin('pe.field', 'f')
            ->where('pe.program = :program')
            ->setParameter('program', $program)
            ->getQuery()
            ->getResult()
        ;

        return array_column($categories, 'category');
    }
}
