<?php


namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class LenderTaxExemptionRepository extends EntityRepository
{

    public function isLenderExemptedInYear(Wallet $wallet, $year)
    {
        $qb = $this->createQueryBuilder('lte');
        $qb->select('COUNT(lte.idLenderTaxExemption)')
            ->where('lte.idLender = :idLender')
            ->andWhere('lte.year = :year')
            ->setParameter('idLender', $wallet)
            ->setParameter('year', $year);

        $result =  $qb->getQuery()->getSingleScalarResult();

        return $result > 0;
    }

}
