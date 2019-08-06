<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Unilend\Entity\Companies;
use Unilend\Repository\Traits\OrderByHandlerTrait;

/**
 * @method Companies|null find($id, $lockMode = null, $lockVersion = null)
 * @method Companies|null findOneBy(array $criteria, array $orderBy = null)
 * @method Companies[]    findAll()
 * @method Companies[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompaniesRepository extends ServiceEntityRepository
{
    use OrderByHandlerTrait;

    /**
     * CompaniesRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Companies::class);
    }

    /**
     * @param Companies|null $currentCompany
     * @param array          $orderBy
     *
     * @return Companies[]
     */
    public function findEligibleArrangers(?Companies $currentCompany, array $orderBy = []): iterable
    {
        return $this->createEligibleArrangersQB($currentCompany, $orderBy)->getQuery()->getResult();
    }

    /**
     * @param Companies|null $currentCompany
     * @param array          $orderBy
     *
     * @return QueryBuilder
     */
    public function createEligibleArrangersQB(?Companies $currentCompany, array $orderBy = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->where('c.idCompany IN (:arrangersToSelect)')
            ->setParameter('arrangersToSelect', array_merge(Companies::COMPANY_ELIGIBLE_ARRANGER, [$currentCompany]))
            ;

        $this->handleOrderBy($queryBuilder, $orderBy);

        return $queryBuilder;
    }

    /**
     * @param array $orderBy
     *
     * @return QueryBuilder
     */
    public function createEligibleRunAgentQB(array $orderBy = [])
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->where('c.idCompany IN (:runsToSelect)')
            ->orWhere('c.parent IN (:runsParentToSelect)')
            ->setParameters(['runsToSelect' => Companies::COMPANY_ELIGIBLE_RUN, 'runsParentToSelect' => Companies::COMPANY_SUBSIDIARY_ELIGIBLE_RUN])
            ;

        $this->handleOrderBy($queryBuilder, $orderBy);

        return $queryBuilder;
    }

    /**
     * @param array $orderBy
     *
     * @return Companies[]
     */
    public function findRegionalBanks(array $orderBy = [])
    {
        $queryBuilder = $this->createQueryBuilder('c')->where('c.parent = :casa')->setParameter('casa', Companies::COMPANY_ID_CASA);

        $this->handleOrderBy($queryBuilder, $orderBy);

        return $queryBuilder->getQuery()->getResult();
    }
}
