<?php

namespace Unilend\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Unilend\Entity\InseePays;

class InseePaysRepository extends EntityRepository
{
    /**
     * @param $codeIso
     *
     * @return InseePays|null
     */
    public function findCountryWithCodeIsoLike($codeIso)
    {
        $queryBuilder = $this->createQueryBuilder('ip');
        $queryBuilder->where('ip.codeiso2 LIKE :code')
            ->setParameter('code', $codeIso)
            ->setCacheable(true);

        try {
            $inseeCountry = $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $exception) {
            return null;
        }

        return $inseeCountry;
    }
}
