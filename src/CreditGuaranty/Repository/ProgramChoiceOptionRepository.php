<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\CreditGuaranty\Entity\ProgramChoiceOption;

/**
 * @method ProgramChoiceOption|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProgramChoiceOption|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProgramChoiceOption[]    findAll()
 * @method ProgramChoiceOption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProgramChoiceOptionRepository extends ServiceEntityRepository
{
    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, ProgramChoiceOption::class);
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProgramChoiceOption $programChoiceOption): void
    {
        $this->getEntityManager()->persist($programChoiceOption);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws ORMException
     */
    public function remove(ProgramChoiceOption $programChoiceOption): void
    {
        $this->getEntityManager()->remove($programChoiceOption);
        $this->getEntityManager()->flush();
    }

    public function resetManager(): void
    {
        $this->managerRegistry->resetManager();
    }
}
