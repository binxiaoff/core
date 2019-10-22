<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\TrancheOffer;

/**
 * @method TrancheOffer|null find($id, $lockMode = null, $lockVersion = null)
 * @method TrancheOffer|null findOneBy(array $criteria, array $orderBy = null)
 * @method TrancheOffer[]    findAll()
 * @method TrancheOffer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrancheOfferRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrancheOffer::class);
    }

    /**
     * @param TrancheOffer $trancheOffer
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(TrancheOffer $trancheOffer): void
    {
        $this->getEntityManager()->persist($trancheOffer);
        $this->getEntityManager()->flush();
    }
}
