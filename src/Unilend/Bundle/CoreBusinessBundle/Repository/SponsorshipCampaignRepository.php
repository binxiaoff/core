<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign;

class SponsorshipCampaignRepository extends EntityRepository
{
    /**
     * @return null|SponsorshipCampaign
     */
    public function findCurrentCampaign()
    {
        $queryBuilder = $this->createQueryBuilder('sc');
        $queryBuilder->where('sc.status = :valid')
            ->andWhere('sc.start <= :now')
            ->andWhere('sc.end IS NULL')
            ->setParameter('valid', SponsorshipCampaign::STATUS_VALID)
            ->setParameter('now', new \DateTime('now'));

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
