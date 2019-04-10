<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\Clients;
use Unilend\Entity\OffresBienvenues;

class OffresBienvenuesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OffresBienvenues::class);
    }

    /**
     * @param Clients $client
     * @param string  $type
     *
     * @return mixed
     */
    public function getWelcomeOfferForClient(Clients $client, $type)
    {
        $queryBuilder = $this->createQueryBuilder('ob');
        $queryBuilder->where('ob.debut <= :clientAdded')
            ->andWhere('ob.fin IS NULL OR ob.fin >= :clientAdded')
            ->andWhere('ob.type = :type')
            ->setParameter('clientAdded', $client->getAdded())
            ->setParameter('type', $type);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
