<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\{Companies, CompanyBeneficialOwnerDeclaration};

class CompanyBeneficialOwnerDeclarationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyBeneficialOwnerDeclaration::class);
    }

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
