<?php

namespace Unilend\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Entity\Clients;

class OffresBienvenuesRepository extends EntityRepository
{
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
