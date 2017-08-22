<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class SponsorshipBlacklistRepository extends EntityRepository
{
    /**
     * @param $idClient
     * @param $idCampaign
     *
     * @return mixed
     */
    public function findBlacklistForClient($idClient, $idCampaign)
    {
        $queryBuilder  = $this->createQueryBuilder('sb');
        $queryBuilder->where('idClient = :idClient')
            ->andWhere('idCampaign = :idCampaign OR idCampaign IS NULL')
            ->setParameter('idClient', $idClient)
            ->setParameter('idCampaign', $idCampaign);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
