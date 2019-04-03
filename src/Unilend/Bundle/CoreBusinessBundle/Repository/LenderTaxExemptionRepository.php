<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Entity\Wallet;

class LenderTaxExemptionRepository extends EntityRepository
{
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
