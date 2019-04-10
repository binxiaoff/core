<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Unilend\Entity\InseePays;

class InseePaysRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InseePays::class);
    }

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
