<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRateSettings;

class ProjectRateSettingsRepository extends EntityRepository
{
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
