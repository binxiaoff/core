<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyBeneficialOwnerDeclaration;

class CompanyBeneficialOwnerDeclarationRepository extends EntityRepository
{
    /**
     * @param int|Companies $idCompany
     *
     * @return null|CompanyBeneficialOwnerDeclaration
     */
    public function findCurrentDeclarationByCompany($idCompany)
    {
        $queryBuilder = $this->createQueryBuilder('cbod');
        $queryBuilder->where('cbod.idCompany = :idCompany')
            ->andWhere('cbod.status != :archived')
            ->setParameter('idCompany', $idCompany)
            ->setParameter('archived', CompanyBeneficialOwnerDeclaration::STATUS_ARCHIVED);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
