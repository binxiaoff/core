<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\{LenderTaxExemption, Wallet};

class LenderTaxExemptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LenderTaxExemption::class);
    }

    public function isLenderExemptedInYear(Wallet $wallet, $year)
    {
        $queryBuilder = $this->createQueryBuilder('lte');
        $queryBuilder->select('COUNT(lte.idLenderTaxExemption)')
            ->where('lte.idLender = :idLender')
            ->andWhere('lte.year = :year')
            ->setParameter('idLender', $wallet)
            ->setParameter('year', $year);

        $result =  $queryBuilder->getQuery()->getSingleScalarResult();

        return $result > 0;
    }
}
