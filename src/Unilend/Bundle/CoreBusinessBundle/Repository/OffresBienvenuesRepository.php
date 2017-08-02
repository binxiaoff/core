<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class OffresBienvenuesRepository extends EntityRepository
{
    public function getValidWelcomeOffer()
    {
        $queryBuilder = $this->createQueryBuilder('ob');
        $queryBuilder->where('status = :online')
            ->andWhere('fin IS NULL');
    }

}
