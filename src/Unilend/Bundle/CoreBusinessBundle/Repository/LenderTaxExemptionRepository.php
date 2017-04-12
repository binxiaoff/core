<?php


namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class LenderTaxExemptionRepository extends EntityRepository
{

    public function isLenderExemptedInYear(Wallet $wallet, $year)
    {
        $match = $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idWallet' => $wallet]);

        $qb = $this->createQueryBuilder('lte');
        $qb->select('COUNT(lte.idLenderTaxExemption)')
            ->where('lte.idLender = :idLender')
            ->andWhere('lte.year = :year')
            ->setParameter('idLender', $match->getIdLenderAccount()->getIdLenderAccount())
            ->setParameter('year', $year);

        $result =  $qb->getQuery()->getSingleScalarResult();

        return $result > 0;
    }

}
