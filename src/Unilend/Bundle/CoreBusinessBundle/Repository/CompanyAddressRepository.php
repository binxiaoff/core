<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\AddressType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyAddress;

class CompanyAddressRepository extends EntityRepository
{
    /**
     * @param Companies|int      $idCompany
     * @param AddressType|string $type
     *
     * @return CompanyAddress|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastModifiedCompanyAddressByType($idCompany, $type): ?CompanyAddress
    {
        $queryBuilder = $this->createQueryBuilder('ca');
        $queryBuilder
            ->select('ca', 'COALESCE(ca.updated, ca.datePending) AS HIDDEN dateOrder')
            ->innerJoin('UnilendCoreBusinessBundle:AddressType', 'at', Join::WITH, 'ca.idType = at.id')
            ->where('ca.idCompany = :idCompany')
            ->andWhere('at.label = :type')
            ->andWhere('ca.dateArchived IS NULL')
            ->orderBy('dateOrder', 'DESC')
            ->setParameter('idCompany', $idCompany)
            ->setParameter('type', $type)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Companies|int $idCompany
     *
     * @return null|CompanyAddress
     * @throws \Doctrine\ORM\NonUniqueResultException,
     */
    public function findValidatedMainCompanyAddress($idCompany): ?CompanyAddress
    {
        $queryBuilder = $this->createQueryBuilder('ca');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:AddressType', 'at', Join::WITH, 'co.idType = at.id')
            ->where('ca.idCompany = :idCompany')
            ->andWhere('at.label = :type')
            ->andWhere('ba.dateValidated IS NOT NULL')
            ->andWhere('ba.dateArchived IS NULL')
            ->orderBy('ba.dateValidated', 'DESC')
            ->setParameter(':idCompany', $idCompany)
            ->setParameter('type', AddressType::TYPE_MAIN_ADDRESS)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
