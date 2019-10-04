<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException, QueryBuilder};
use Unilend\Entity\{Clients, Project, ProjectStatus};
use Unilend\Repository\Traits\{OrderByHandlerTrait, PaginationHandlerTrait};

/**
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    use OrderByHandlerTrait;
    use PaginationHandlerTrait;

    /**
     * ProjectRepository constructor.
     *
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Project::class);
    }

    /**
     * @param Project $project
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Project $project)
    {
        $this->getEntityManager()->persist($project);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Clients    $client
     * @param array|null $orderBy
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return iterable
     */
    public function findListableByClient(Clients $client, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): iterable
    {
        $queryBuilder = $this->getListableByClientQueryBuilder($client);

        $this->handleOrderBy($queryBuilder, $orderBy);
        $this->handlePagination($queryBuilder, $limit, $offset);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Clients $client
     *
     * @throws NonUniqueResultException
     *
     * @return int
     */
    public function countListableByClient(Clients $client): int
    {
        $queryBuilder = $this->getListableByClientQueryBuilder($client);
        $queryBuilder->select('COUNT(DISTINCT p.id)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Clients $client
     *
     * @return QueryBuilder
     */
    private function getListableByClientQueryBuilder(Clients $client): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder
            ->innerJoin('p.projectParticipations', 'pp')
            ->innerJoin('p.currentStatus', 'cpsh')
            ->where('cpsh.status IN (:status)')
            ->andWhere('pp.company = :company OR p.submitterCompany = :company')
            ->setParameter('company', $client->getCompany())
            ->setParameter('status', ProjectStatus::DISPLAYABLE_STATUS)
        ;

        return $queryBuilder;
    }
}
