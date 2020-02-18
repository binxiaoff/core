<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException, QueryBuilder};
use Unilend\Entity\Company;
use Unilend\Repository\Traits\OrderByHandlerTrait;

/**
 * @method Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Company[]    findAll()
 * @method Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyRepository extends ServiceEntityRepository
{
    use OrderByHandlerTrait;

    /**
     * CompanyRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * @param Company $company
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Company $company): void
    {
        $this->getEntityManager()->persist($company);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Company|null $currentCompany
     * @param array        $orderBy
     *
     * @return Company[]
     */
    public function findEligibleArrangers(?Company $currentCompany, array $orderBy = []): iterable
    {
        return $this->createEligibleArrangersQB($currentCompany, $orderBy)->getQuery()->getResult();
    }

    /**
     * @param Company|null $currentCompany
     * @param array        $orderBy
     *
     * @return QueryBuilder
     */
    public function createEligibleArrangersQB(?Company $currentCompany, array $orderBy = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->where('c.id IN (:arrangersToSelect)')
            ->setParameter('arrangersToSelect', array_merge(Company::COMPANY_ELIGIBLE_ARRANGER, [$currentCompany]))
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
            ->where('c.id IN (:runsToSelect)')
            ->orWhere('c.parent IN (:runsParentToSelect)')
            ->setParameters(['runsToSelect' => Company::COMPANY_ELIGIBLE_RUN, 'runsParentToSelect' => Company::COMPANY_SUBSIDIARY_ELIGIBLE_RUN])
            ;

        $this->handleOrderBy($queryBuilder, $orderBy);

        return $queryBuilder;
    }

    /**
     * @param array $orderBy
     *
     * @return Company[]
     */
    public function findRegionalBanks(array $orderBy = []): iterable
    {
        $queryBuilder = $this->createQueryBuilder('c')->where('c.parent = :casa')->setParameter('casa', Company::COMPANY_ID_CASA);

        $this->handleOrderBy($queryBuilder, $orderBy);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $name
     * @param int    $maxResult
     *
     * @return array|array[]
     */
    public function findByName(string $name, ?int $maxResult = null): array
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c.name, c.siren, c.id as id')
            ->where('c.name LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->setMaxResults($maxResult)
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}
