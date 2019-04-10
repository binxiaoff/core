<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\{ProjectCharge, Receptions};

class ProjectChargeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectCharge::class);
    }

    /**
     * @param Receptions|int $wireTransferIn
     *
     * @return float
     */
    public function getTotalChargeByWireTransferIn($wireTransferIn)
    {
        $queryBuilder = $this->createQueryBuilder('pc');
        $queryBuilder->select('SUM(pc.amountInclVat)')
            ->where('pc.idWireTransferIn = :wireTransferIn')
            ->setParameter('wireTransferIn', $wireTransferIn);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
