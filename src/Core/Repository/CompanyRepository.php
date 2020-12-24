<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\{
    NoResultException,
    NonUniqueResultException,
    ORMException,
    OptimisticLockException
};
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Team;
use Unilend\Core\Repository\Traits\OrderByHandlerTrait;

/**
 * @method Company|null find($id, $lockMode = null, $lockVersion = null)
 * @method Company|null findOneBy(array $criteria, array $orderBy = null)
 * @method Company[]    findAll()
 * @method Company[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompanyRepository extends ServiceEntityRepository
{
    use OrderByHandlerTrait;

    /** @var TeamRepository */
    private TeamRepository $teamRepository;

    /**
     * @param ManagerRegistry $registry
     * @param TeamRepository  $teamRepository
     */
    public function __construct(
        ManagerRegistry $registry,
        TeamRepository $teamRepository
    ) {
        parent::__construct($registry, Company::class);
        $this->teamRepository = $teamRepository;
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
     * @param Team $team
     *
     * @return Company
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findOneByTeam(Team $team): Company
    {
        $alias = 'core_company';

        $cteName = 'tree';

        $team = $team->getId() ? $team : $team->getParent();

        $cte = $this->teamRepository->getRootPathTableCommonTableExpression($team, $cteName);

        $resultSetMapping = $this->createResultSetMappingBuilder($alias);

        $select = $resultSetMapping->generateSelectClause();

        $sql = "{$cte} SELECT {$select} FROM {$cteName} INNER JOIN {$alias} ON {$alias}.id_root_team = {$cteName}.id";

        return $this->getEntityManager()
            ->createNativeQuery($sql, $resultSetMapping)
            ->setResultSetMapping($resultSetMapping)
            ->getSingleResult();
    }
}
