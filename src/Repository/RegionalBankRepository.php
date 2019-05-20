<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Unilend\Entity\RegionalBank;
use Unilend\Repository\Traits\OrderByHandlerTrait;

/**
 * @method RegionalBank|null find($id, $lockMode = null, $lockVersion = null)
 * @method RegionalBank|null findOneBy(array $criteria, array $orderBy = null)
 * @method RegionalBank[]    findAll()
 * @method RegionalBank[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegionalBankRepository extends ServiceEntityRepository
{
    use OrderByHandlerTrait;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RegionalBank::class);
    }

    /**
     * @param int   $friendLyGroup
     * @param array $orderBy
     *
     * @return iterable
     */
    public function getRegionalBankIds(?int $friendLyGroup, array $orderBy = []): iterable
    {
        $queryBuilder = $this->createQueryBuilder('rb')->select('IDENTITY(rb.company) AS id')->innerJoin('rb.company', 'c');

        if ($friendLyGroup) {
            $queryBuilder
                ->where('rb.friendlyGroup = :friendlyGroup')
                ->setParameter('friendlyGroup', $friendLyGroup)
            ;
        }

        if ($orderBy) {
            $this->handleOrderBy($queryBuilder, $orderBy);
        }

        return $queryBuilder->getQuery()->getResult('HYDRATE_COLUMN');
    }
}
