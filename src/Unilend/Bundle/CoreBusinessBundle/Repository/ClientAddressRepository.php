<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{AddressType, ClientAddress, Clients, Wallet, WalletType};

class ClientAddressRepository extends EntityRepository
{
    /**
     * @param Clients|int        $idClient
     * @param AddressType|string $type
     *
     * @return ClientAddress|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findLastModifiedNotArchivedAddressByType($idClient, $type): ?ClientAddress
    {
        $typeLabel = $type instanceof AddressType ? $type->getLabel() : $type;

        $queryBuilder = $this->createQueryBuilder('ca');
        $queryBuilder
            ->select('ca', 'COALESCE(ca.updated, ca.datePending) AS HIDDEN dateOrder')
            ->innerJoin(AddressType::class, 'at', Join::WITH, 'ca.idType = at.id')
            ->where('ca.idClient = :idClient')
            ->andWhere('at.label = :type')
            ->andWhere('ca.dateArchived IS NULL')
            ->orderBy('dateOrder', 'DESC')
            ->setParameter('idClient', $idClient)
            ->setParameter('type', $typeLabel)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Clients|int $idClient
     *
     * @return null|ClientAddress
     * @throws \Doctrine\ORM\NonUniqueResultException,
     */
    public function findValidatedMainClientAddress($idClient): ?ClientAddress
    {
        $queryBuilder = $this->createQueryBuilder('ca');
        $queryBuilder
            ->innerJoin(AddressType::class, 'at', Join::WITH, 'ca.idType = at.id')
            ->where('ca.idClient = :idClient')
            ->andWhere('at.label = :type')
            ->andWhere('ca.dateValidated IS NOT NULL')
            ->andWhere('ca.dateArchived IS NULL')
            ->orderBy('ca.dateValidated', 'DESC')
            ->setParameter('idClient', $idClient)
            ->setParameter('type', AddressType::TYPE_MAIN_ADDRESS)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param int $limit
     *
     * @return ClientAddress[]
     */
    public function findLenderAddressWithoutCog(int $limit)
    {
        $queryBuilder = $this->createQueryBuilder('ca');
        $queryBuilder
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'w.idClient = ca.idClient')
            ->innerJoin(WalletType::class, 'wt', Join::WITH, 'w.idType = wt.id')
            ->where('ca.cog IS NULL')
            ->andWhere('wt.label = :lender')
            ->setMaxResults($limit)
            ->setParameter('lender', WalletType::LENDER);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \DateTime   $date
     * @param Clients|int $idClient
     *
     * @return ClientAddress|null
     */
    public function findMainAddressAddedBeforeDate(\DateTime $date, $idClient): ?ClientAddress
    {
        $queryBuilder = $this->createQueryBuilder('ca');
        $queryBuilder
            ->innerJoin(AddressType::class, 'at', Join::WITH, 'ca.idType = at.id')
            ->where('ca.idClient = :idClient')
            ->andWhere('at.label = :type')
            ->andWhere('ca.added <= :date')
            ->orderBy('ca.added', 'DESC')
            ->setParameter('date', $date)
            ->setParameter('idClient', $idClient)
            ->setParameter('type', AddressType::TYPE_MAIN_ADDRESS)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
