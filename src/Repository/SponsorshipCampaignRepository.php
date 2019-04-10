<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\SponsorshipCampaign;

class SponsorshipCampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SponsorshipCampaign::class);
    }

    /**
     * @return SponsorshipCampaign|Null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findCurrentCampaign(): ?SponsorshipCampaign
    {
        $queryBuilder = $this->createQueryBuilder('sc');
        $queryBuilder->where('sc.status = :valid')
            ->andWhere('sc.start <= CURRENT_DATE()')
            ->andWhere('sc.end >= CURRENT_DATE()')
            ->setParameter('valid', SponsorshipCampaign::STATUS_VALID);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param \DateTime $date
     *
     * @return SponsorshipCampaign|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findCampaignValidAtDate(\DateTime $date): ?SponsorshipCampaign
    {
        $queryBuilder = $this->createQueryBuilder('sc');
        $queryBuilder->where('sc.start <= :date')
            ->andWhere('sc.end >= :date')
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
