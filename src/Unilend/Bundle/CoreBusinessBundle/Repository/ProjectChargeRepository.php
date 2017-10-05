<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;

class ProjectChargeRepository extends EntityRepository
{
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
