<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{AddressType, Companies, CompanyAddress, Wallet, WalletType};

class CompanyAddressRepository extends EntityRepository
{
    /**
     * @param Companies|int      $idCompany
     * @param AddressType|string $type
     *
     * @return CompanyAddress|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastModifiedNotArchivedAddressByType($idCompany, $type): ?CompanyAddress
    {
        $typeLabel = $type instanceof AddressType ? $type->getLabel() : $type;

        $queryBuilder = $this->createQueryBuilder('ca');
        $queryBuilder
            ->select('ca', 'COALESCE(ca.updated, ca.datePending) AS HIDDEN dateOrder')
            ->innerJoin(AddressType::class, 'at', Join::WITH, 'ca.idType = at.id')
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
            ->innerJoin(AddressType::class, 'at', Join::WITH, 'ca.idType = at.id')
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

    /**
     * @param int $limit
     *
     * @return CompanyAddress[]
     */
    public function findLenderAddressWithoutCog(int $limit)
    {
        $queryBuilder = $this->createQueryBuilder('ca');
        $queryBuilder
            ->innerJoin(Companies::class, 'co', Join::WITH, 'ca.idCompany = co.idCompany')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'w.idClient = co.idClientOwner')
            ->innerJoin(WalletType::class, 'wt', Join::WITH, 'w.idType = wt.id')
            ->where('ca.cog IS NULL')
            ->andWhere('wt.label = :lender')
            ->setMaxResults($limit)
            ->setParameter('lender', WalletType::LENDER);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \DateTime     $date
     * @param Companies|int $idCompany
     *
     * @return null|CompanyAddress
     */
    public function findMainAddressAddedBeforeDate(\DateTime $date, $idCompany): ?CompanyAddress
    {
        $queryBuilder = $this->createQueryBuilder('ca');
        $queryBuilder
            ->innerJoin(AddressType::class, 'at', Join::WITH, 'ca.idType = at.id')
            ->where('ca.idCompany = :idCompany')
            ->andWhere('at.label = :type')
            ->andWhere('ca.added <= :date')
            ->orderBy('ca.added', 'DESC')
            ->setParameter('date', $date)
            ->setParameter(':idCompany', $idCompany)
            ->setParameter('type', AddressType::TYPE_MAIN_ADDRESS)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
