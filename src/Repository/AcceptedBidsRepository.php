<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\AcceptedBids;

/**
 * @method AcceptedBids|null find($id, $lockMode = null, $lockVersion = null)
 * @method AcceptedBids|null findOneBy(array $criteria, array $orderBy = null)
 * @method AcceptedBids[]    findAll()
 * @method AcceptedBids[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcceptedBidsRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcceptedBids::class);
    }

    /**
     * @param AcceptedBids $acceptedBid
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(AcceptedBids $acceptedBid): void
    {
        $this->getEntityManager()->persist($acceptedBid);
        $this->getEntityManager()->flush();
    }
}
