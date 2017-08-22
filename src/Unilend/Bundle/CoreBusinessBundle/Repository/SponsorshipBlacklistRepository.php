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
        $queryBuilder->where('sb.idClient = :idClient')
            ->andWhere('sb.idCampaign = :idCampaign OR sb.idCampaign IS NULL')
            ->setParameter('idClient', $idClient)
            ->setParameter('idCampaign', $idCampaign);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
