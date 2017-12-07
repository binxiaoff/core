<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Unilend\Bundle\CoreBusinessBundle\Entity\InseePays;

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
        $queryBuilder->where('CODEISO2 LIKE :code')
            ->setParameter('code', $codeIso);

        try {
            $inseeCountry = $queryBuilder->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $exception) {
            return null;
        }

        return $inseeCountry;
    }
}
