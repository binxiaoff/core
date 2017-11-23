<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;

class ProjectBeneficialOwnerRepository extends EntityRepository
{
    /**
     * @param int|Companies $company
     *
     * @return array
     */
    public function findAllDeclarationsForCompany($company)
    {
        $queryBuilder = $this->createQueryBuilder('pbou');
        $queryBuilder->leftJoin('UnilendCoreBusinessBundle:CompanyBeneficialOwnerDeclaration', 'cbod', Join::WITH, 'cbod.id = pbou.idDeclaration')
            ->where('cbod.idCompany = :idCompany')
            ->andWhere('pbou.status != :cancelled')
            ->setParameter('idCompany', $company)
            ->setParameter('cancelled' , UniversignEntityInterface::STATUS_CANCELED);

        return $queryBuilder->getQuery()->getResult();
    }
}
