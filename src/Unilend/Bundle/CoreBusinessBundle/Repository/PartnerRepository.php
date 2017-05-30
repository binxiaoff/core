<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class PartnerRepository extends EntityRepository
{
    /**
     * @param null|int $status
     *
     * @return array
     */
    public function getPartnersSortedByName($status)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder
            ->join('p.idCompany', 'c')
            ->orderBy('c.name');

        if (null !== $status) {
            $queryBuilder
                ->where('p.status = :partnerStatus')
                ->setParameter('partnerStatus', $status);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
