<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    ClientDataHistory, Clients
};

class ClientDataHistoryRepository extends EntityRepository
{
    /**
     * @param Clients $client
     *
     * @return ClientDataHistory[]
     */
    public function findLastModifiedDataToValidate(Clients $client): array
    {
        $queryBuilder = $this->createQueryBuilder('cdh');
        $queryBuilder
            ->leftJoin('UnilendCoreBusinessBundle:ClientDataHistory', 'cdh2', Join::WITH, 'cdh.field = cdh2.field AND cdh.datePending < cdh2.datePending')
            ->where('cdh.idClient = :client')
            ->andWhere('cdh.dateValidated IS NULL')
            ->andWhere('cdh2.id IS NULL')
            ->setParameter('client', $client);

        return $queryBuilder->getQuery()->getResult();
    }
}
