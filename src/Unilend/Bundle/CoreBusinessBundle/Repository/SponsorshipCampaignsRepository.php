<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaigns;

class SponsorshipCampaignsRepository extends EntityRepository
{
    /**
     * @return null|SponsorshipCampaigns
     */
    public function findCurrentCampaign()
    {
        $queryBuilder = $this->createQueryBuilder('sc');
        $queryBuilder->where('sc.status = :valid')
            ->andWhere('sc.start <= :now')
            ->andWhere('sc.end IS NULL')
            ->setParameter('valid', SponsorshipCampaigns::STATUS_VALID)
            ->setParameter('now', new \DateTime('now'));

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
