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
            ->andWhere('sc.end >= :now')
            ->setParameter('valid', SponsorshipCampaign::STATUS_VALID)
            ->setParameter('now', new \DateTime('now'));

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param \DateTime $date
     *
     * @return mixed
     */
    public function findCampaignValidAtDate(\DateTime $date)
    {
        $queryBuilder = $this->createQueryBuilder('sc');
        $queryBuilder->where('sc.start <= :date')
            ->andWhere('sc.end >= :date')
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
