<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{Companies, CompanyBeneficialOwnerDeclaration, ProjectBeneficialOwnerUniversign, UniversignEntityInterface};

class ProjectBeneficialOwnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectBeneficialOwnerUniversign::class);
    }

    /**
     * @param int|Companies $company
     *
     * @return array
     */
    public function findAllDeclarationsForCompany($company)
    {
        $queryBuilder = $this->createQueryBuilder('pbou');
        $queryBuilder->leftJoin(CompanyBeneficialOwnerDeclaration::class, 'cbod', Join::WITH, 'cbod.id = pbou.idDeclaration')
            ->where('cbod.idCompany = :idCompany')
            ->andWhere('pbou.status != :cancelled')
            ->setParameter('idCompany', $company)
            ->setParameter('cancelled' , UniversignEntityInterface::STATUS_CANCELED);

        return $queryBuilder->getQuery()->getResult();
    }
}
