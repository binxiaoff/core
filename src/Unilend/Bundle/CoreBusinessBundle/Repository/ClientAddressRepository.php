<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    AddressType, ClientAddress, Clients
};

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
            ->innerJoin('UnilendCoreBusinessBundle:AddressType', 'at', Join::WITH, 'ca.idType = at.id')
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
     * @param AddressType|string $type
     *
     * @return null|ClientAddress
     * @throws \Doctrine\ORM\NonUniqueResultException,
     */
    public function findValidatedClientAddress($idClient, $type): ?ClientAddress
    {
        $typeLabel = $type instanceof AddressType ? $type->getLabel() : $type;

        $queryBuilder = $this->createQueryBuilder('ca');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:AddressType', 'at', Join::WITH, 'ca.idType = at.id')
            ->where('ca.idClient = :idClient')
            ->andWhere('at.label = :type')
            ->andWhere('ca.dateValidated IS NOT NULL')
            ->andWhere('ca.dateArchived IS NULL')
            ->orderBy('ca.dateValidated', 'DESC')
            ->setParameter(':idClient', $idClient)
            ->setParameter('type', $typeLabel)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
