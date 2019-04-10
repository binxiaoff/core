<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{ClientDataHistory, Clients};

class ClientDataHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientDataHistory::class);
    }

    /**
     * @param Clients $client
     *
     * @return ClientDataHistory[]
     */
    public function findLastModifiedDataToValidate(Clients $client): array
    {
        $queryBuilder = $this->createQueryBuilder('cdh');
        $queryBuilder
            ->leftJoin(ClientDataHistory::class, 'cdh2', Join::WITH, 'cdh.field = cdh2.field AND cdh.datePending < cdh2.datePending')
            ->where('cdh.idClient = :client')
            ->andWhere('cdh.dateValidated IS NULL')
            ->andWhere('cdh2.id IS NULL')
            ->setParameter('client', $client);

        return $queryBuilder->getQuery()->getResult();
    }
}
