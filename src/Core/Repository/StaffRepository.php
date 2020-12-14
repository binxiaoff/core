<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Staff;

/**
 * @method Staff|null find($id, $lockMode = null, $lockVersion = null)
 * @method Staff|null findOneBy(array $criteria, array $orderBy = null)
 * @method Staff[]    findAll()
 * @method Staff[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StaffRepository extends ServiceEntityRepository
{
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
        parent::__construct($registry, Staff::class);
        $this->teamRepository = $teamRepository;
    }

    /**
     * @param Staff $staff
     *
     * @throws ORMException
     */
    public function refresh(Staff $staff): void
    {
        $this->getEntityManager()->refresh($staff);
    }

    /**
     * @param string  $email
     * @param Company $company
     *
     * @throws NonUniqueResultException
     *
     * @return Staff|null
     */
    public function findOneByEmailAndCompany(string $email, Company $company): ?Staff
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.user', 'u')
            ->innerJoin('s.team', 't')
            ->innerJoin('t.incomingEdges', 'i')
            ->where(
                'u.email = :email',
                's.team = :rootTeam OR i.ancestor = :rootTeam'
            )
            ->setParameters(['email' => $email, 'rootTeam' => $company->getRootTeam()])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param Staff $superior
     * @param Staff $subordinate
     *
     * @return bool
     */
    public function isSuperior(Staff $superior, Staff $subordinate): bool
    {
        if (false === $superior->isManager()) {
            return false;
        }

        $resultSetMapping = new ResultSetMapping();

        $resultSetMapping->addScalarResult('success', 'success', Types::BOOLEAN);

        $cteName = 'tree';

        $cte = $this->teamRepository->getRootPathTableCommonTableExpression($subordinate->getTeam(), $cteName);

        $sql = <<<SQL
{$cte}
SELECT 1 as success 
FROM core_staff
INNER JOIN {$cteName} ON core_staff.id_team = {$cteName}.id
WHERE core_staff.manager = 1 AND core_staff.id = :superior
SQL;

        $result = $this->getEntityManager()
            ->createNativeQuery($sql, $resultSetMapping)
            ->setParameter('superior', $superior->getId())
            ->getResult();

        return false === empty($result);
    }

    /**
     * @param Company $company
     *
     * @return iterable|Staff[]
     */
    public function findByCompany(Company $company): iterable
    {
        $tableName = $this->getClassMetadata()->getTableName();

        $resultSetMapping = $this->createResultSetMappingBuilder($tableName);

        $cteName = 'tree';

        $cte = $this->teamRepository->getSubtreeTableCommonTableExpression($company->getRootTeam(), $cteName);

        $select = $resultSetMapping->generateSelectClause();

        $sql = <<<SQL
{$cte}
SELECT {$select} 
FROM core_staff INNER JOIN {$cteName} ON core_staff.id_team = {$cteName}.id
SQL;

        return $this->getEntityManager()
            ->createNativeQuery($sql, $resultSetMapping)
            ->getResult();
    }
}
