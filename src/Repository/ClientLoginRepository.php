<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\ClientLogin;

/**
 * @method ClientLogin|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientLogin|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientLogin[]    findAll()
 * @method ClientLogin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientLoginRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientLogin::class);
    }

    /**
     * @param ClientLogin $login
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ClientLogin $login): void
    {
        $this->getEntityManager()->persist($login);
        $this->getEntityManager()->flush();
    }
}
