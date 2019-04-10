<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\SponsorshipBlacklist;

class SponsorshipBlacklistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SponsorshipBlacklist::class);
    }

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
