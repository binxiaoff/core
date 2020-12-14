<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Team;

/**
 * @method Team|null find($id, $lockMode = null, $lockVersion = null)
 * @method Team|null findOneBy(array $criteria, array $orderBy = null)
 * @method Team[]    findAll()
 * @method Team[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    /**
     * @param Team $team
     *
     * @return iterable|Team[]
     */
    public function findRootPath(Team $team): iterable
    {
        $cteName = 'tree';

        return $this->findByCommonTableExpression($this->getRootPathTableCommonTableExpression($team, $cteName), $cteName);
    }


    /**
     * @param Team $team
     *
     * @return iterable|Team[]
     */
    public function findSubtree(Team $team): iterable
    {
        $cteName = 'tree';

        return $this->findByCommonTableExpression($this->getSubtreeTableCommonTableExpression($team, $cteName), $cteName);
    }

    /**
     * @param Team   $team
     * @param string $cteName
     *
     * @return string
     */
    public function getRootPathTableCommonTableExpression(Team $team, string $cteName)
    {
        return <<<SQL
WITH RECURSIVE {$cteName} AS (
    SELECT core_team.*
    FROM core_team
    WHERE id = {$team->getId()}
    UNION ALL
    SELECT core_team.*
    FROM core_team
    INNER JOIN {$cteName} ON {$cteName}.id_parent = core_team.id
)
SQL;
    }

    /**
     * @param Team   $team
     * @param string $cteName
     *
     * @return string
     */
    public function getSubtreeTableCommonTableExpression(Team $team, string $cteName)
    {
        return <<<SQL
WITH RECURSIVE {$cteName} AS (
    SELECT core_team.*
    FROM core_team
    WHERE id = {$team->getId()}
    UNION ALL
    SELECT core_team.*
    FROM core_team
    INNER JOIN {$cteName} ON {$cteName}.id = core_team.id_parent
)
SQL;
    }

    /**
     * @param Company $data
     *
     * @return iterable|Team[]
     */
    public function findByCompany(Company $data)
    {
        return $this->findSubtree($data->getRootTeam());
    }

    /**
     * @param Team $query
     * @param Team $leaf
     *
     * @return bool
     */
    public function isRootPathNode(Team $query, Team $leaf): bool
    {
        $resultSetMapping = new ResultSetMapping();

        $resultSetMapping->addScalarResult('success', 'success', Types::BOOLEAN);

        $cteName = 'tree';

        $cte = $this->getRootPathTableCommonTableExpression($leaf, $cteName);

        $sql = <<<SQL
{$cte} SELECT 1 as success FROM {$cteName}
WHERE {$cteName}.id = :parent
SQL;

        $result = $this->getEntityManager()
            ->createNativeQuery($sql, $resultSetMapping)
            ->setParameter('parent', $query->getId())
            ->getResult();

        return false === empty($result);
    }

    /**
     * @param string $cte
     * @param string $cteName
     *
     * @return int|mixed|string
     */
    private function findByCommonTableExpression(string $cte, string $cteName): iterable
    {
        $alias = 'team';

        $resultSetMapping = $this->createResultSetMappingBuilder($alias);

        $select = $resultSetMapping->generateSelectClause();

        $sql = "{$cte} SELECT {$select} FROM {$cteName} {$alias}";

        return $this->getEntityManager()
            ->createNativeQuery($sql, $resultSetMapping)
            ->setResultSetMapping($resultSetMapping)
            ->getResult();
    }
}
