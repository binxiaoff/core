<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, Companies, CompanyAddress
};

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
        $typeLabel = $type instanceof AddressType ? $type->getLabel() : $type;

        $queryBuilder = $this->createQueryBuilder('ca');
        $queryBuilder
            ->select('ca', 'COALESCE(ca.updated, ca.datePending) AS HIDDEN dateOrder')
            ->innerJoin('UnilendCoreBusinessBundle:AddressType', 'at', Join::WITH, 'ca.idType = at.id')
            ->where('ca.idCompany = :idCompany')
            ->andWhere('at.label = :type')
            ->andWhere('ca.dateArchived IS NULL')
            ->orderBy('dateOrder', 'DESC')
            ->setParameter('idCompany', $idCompany)
            ->setParameter('type', $typeLabel)
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
            ->innerJoin('UnilendCoreBusinessBundle:AddressType', 'at', Join::WITH, 'ca.idType = at.id')
            ->where('ca.idCompany = :idCompany')
            ->andWhere('at.label = :type')
            ->andWhere('ca.dateValidated IS NOT NULL')
            ->andWhere('ca.dateArchived IS NULL')
            ->orderBy('ca.dateValidated', 'DESC')
            ->setParameter(':idCompany', $idCompany)
            ->setParameter('type', AddressType::TYPE_MAIN_ADDRESS)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
