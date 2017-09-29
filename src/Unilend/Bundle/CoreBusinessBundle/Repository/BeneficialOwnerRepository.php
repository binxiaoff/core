<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBeneficialOwnerDeclaration;

class BeneficialOwnerRepository extends EntityRepository
{
    /**
     * @param int|CompanyBeneficialOwnerDeclaration $declaration
     *
     * @return int|null
     */
    public function getCountBeneficialOwnersForDeclaration($declaration)
    {
        $queryBuilder = $this->createQueryBuilder('bo');
        $queryBuilder->select('COUNT(bo.id)')
            ->where('bo.idDeclaration = :idDeclaration')
            ->setParameter('idDeclaration', $declaration);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param $declaration
     *
     * @return mixed
     */
    public function getSumPercentage($declaration)
    {
        $queryBuilder = $this->createQueryBuilder('bo');
        $queryBuilder->select('SUM(bo.percentage)')
            ->where('bo.idDeclaration = :idDeclaration')
            ->setParameter('idDeclaration', $declaration);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
