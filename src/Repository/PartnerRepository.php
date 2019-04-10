<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\Partner;

class PartnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partner::class);
    }

    /**
     * @param null|int $status
     *
     * @return array
     */
    public function getPartnersSortedByName($status = null)
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
