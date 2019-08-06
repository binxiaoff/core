<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\Bids;

/**
 * @method Bids|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bids|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bids[]    findAll()
 * @method Bids[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BidsRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bids::class);
    }

    /**
     * @param Bids $bid
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Bids $bid)
    {
        $trancheRate = $bid->getTranche()->getRate();
        if ($trancheRate->getIndexType()) {
            $bid->getRate()->setIndexType($trancheRate->getIndexType());
        }
        if ($trancheRate->getMargin()) {
            $bid->getRate()->setMargin($trancheRate->getMargin());
        }
        if ($trancheRate->getFloor()) {
            $bid->getRate()->setFloor($trancheRate->getFloor());
        }

        $this->getEntityManager()->persist($bid);
        $this->getEntityManager()->flush();
    }
}
