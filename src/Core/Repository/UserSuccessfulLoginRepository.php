<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\UserSuccessfulLogin;

/**
 * @method UserSuccessfulLogin|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserSuccessfulLogin|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserSuccessfulLogin[]    findAll()
 * @method UserSuccessfulLogin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserSuccessfulLoginRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSuccessfulLogin::class);
    }

    /**
     * @param UserSuccessfulLogin $login
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(UserSuccessfulLogin $login): void
    {
        $this->getEntityManager()->persist($login);
        $this->getEntityManager()->flush();
    }
}
