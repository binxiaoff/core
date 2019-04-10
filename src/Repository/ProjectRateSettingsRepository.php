<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\ORMException;
use Unilend\Entity\ProjectRateSettings;

class ProjectRateSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectRateSettings::class);
    }

    /**
     * @return array|null
     */
    public function getGlobalMinMaxRate(): ?array
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder
            ->select('MIN(p.rateMin) AS rate_min, MAX(p.rateMax) AS rate_max')
            ->where('p.status = :status')
            ->setParameter('status', ProjectRateSettings::STATUS_ACTIVE);

        try {
            return $queryBuilder->getQuery()->getSingleResult();
        } catch (ORMException $exception) {
            return null;
        }
    }
}
