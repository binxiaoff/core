<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\ClientSuccessfulLogin;

/**
 * @method ClientSuccessfulLogin|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientSuccessfulLogin|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientSuccessfulLogin[]    findAll()
 * @method ClientSuccessfulLogin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientSuccessfulLoginRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientSuccessfulLogin::class);
    }

    /**
     * @param ClientSuccessfulLogin $login
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ClientSuccessfulLogin $login): void
    {
        $this->getEntityManager()->persist($login);
        $this->getEntityManager()->flush();
    }
}
