<?php

declare(strict_types=1);

namespace KLS\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\HubspotCompany;
use KLS\Core\Repository\Traits\OrderByHandlerTrait;

/**
 * @method Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Company[]    findAll()
 * @method Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyRepository extends ServiceEntityRepository
{
    use OrderByHandlerTrait;

    private const MAX_COMPANY_LOAD = 100;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Company $company): void
    {
        $this->getEntityManager()->persist($company);
        $this->getEntityManager()->flush();
    }

    public function getCompaniesAsc(?int $companyId = null): ?array
    {
        $qb = $this->createQueryBuilder('c');

        if ($companyId) {
            $qb->andWhere('c.id > :companyId');
            $qb->setParameter('companyId', $companyId);
        }

        return $qb->setMaxResults(self::MAX_COMPANY_LOAD)->getQuery()->getResult();
    }

    public function findCompaniesToCreateOnHubspot(int $limit)
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin(HubspotCompany::class, 'hc', Join::WITH, 'c.id = hc.company')
            ->where('hc.id IS NULL')
        ;

        return $qb->setMaxResults($limit)->getQuery()->getResult();
    }

    public function findCompaniesToUpdateOnHubspot(int $limit)
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin(HubspotCompany::class, 'hc', Join::WITH, 'c.id = hc.company')
            ->where('hc.id IS NOT NULL')
            ->andWhere('c.updated > hc.synchronized')
            ->orderBy('hc.synchronized', 'DESC')
        ;

        return $qb->setMaxResults($limit)->getQuery()->getResult();
    }
}
