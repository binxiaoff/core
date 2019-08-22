<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\Queries;

/**
 * @method Queries|null find($id, $lockMode = null, $lockVersion = null)
 * @method Queries|null findOneBy(array $criteria, array $orderBy = null)
 * @method Queries[]    findAll()
 * @method Queries[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QueriesRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Queries::class);
    }

    /**
     * @param Queries $query
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Queries $query)
    {
        $this->getEntityManager()->persist($query);
        $this->getEntityManager()->flush();
    }
}
