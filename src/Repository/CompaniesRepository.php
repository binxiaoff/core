<?php

namespace Unilend\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use PDO;
use RuntimeException;
use Unilend\Entity\{Companies, CompanyStatus, CompanyStatusHistory, Operation, OperationType, Projects, ProjectsStatus, ProjectsStatusHistory, RiskDataMonitoring, Wallet};
use Unilend\Service\RiskDataMonitoring\MonitoringCycleManager;

/**
 * @method Companies|null find($id, $lockMode = null, $lockVersion = null)
 * @method Companies|null findOneBy(array $criteria, array $orderBy = null)
 * @method Companies[]    findAll()
 * @method Companies[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompaniesRepository extends ServiceEntityRepository
{
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
     * @param int $maxDepositAmount
     *
     * @return array
     */
    public function getLegalEntitiesByCumulativeDepositAmount($maxDepositAmount)
    {
        $operationType = $this->getEntityManager()->getRepository(OperationType::class);

        $queryBuilder = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.idClientOwner) AS idClient, c.capital, SUM(o.amount) AS depositAmount, GROUP_CONCAT(o.id) AS operation')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'c.idClientOwner = w.idClient')
            ->innerJoin(Operation::class, 'o', Join::WITH, 'o.idWalletCreditor = w.id')
            ->where('o.idType = :operation_type')
            ->setParameter('operation_type', $operationType->findOneBy(['label' => OperationType::LENDER_PROVISION]))
            ->groupBy('o.idWalletCreditor')
            ->having('depositAmount >= c.capital')
            ->andHaving('depositAmount >= :max_deposit_amount')
            ->setParameter('max_deposit_amount', $maxDepositAmount)
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string|null $siren
     * @param string|null $provider
     * @param bool        $ongoing
     *
     * @return array
     */
    public function getMonitoredCompaniesBySiren(?string $siren, ?string $provider = null, bool $ongoing = true): array
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->innerJoin(Projects::class, 'p', Join::WITH, 'p.idCompany = c.idCompany')
            ->innerJoin(RiskDataMonitoring::class, 'rdm', Join::WITH, 'c.siren = rdm.siren')
            ->where('c.siren = :siren')
            ->setParameter('siren', $siren)
        ;

        if ($ongoing) {
            $queryBuilder->andWhere('rdm.end IS NULL');
        }

        if (null !== $provider) {
            $queryBuilder->andWhere('rdm.provider = :provider')
                ->setParameter('provider', $provider)
            ;
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     *
     * @throws DBALException
     *
     * @return array
     */
    public function getCountCompaniesInCollectiveProceedingBetweenDates(DateTime $start, DateTime $end)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $status = [
            CompanyStatus::STATUS_PRECAUTIONARY_PROCESS,
            CompanyStatus::STATUS_COMPULSORY_LIQUIDATION,
            CompanyStatus::STATUS_RECEIVERSHIP,
        ];

        $query = 'SELECT COUNT(DISTINCT(csh.id_company))
                  FROM (SELECT MAX(id) AS max_id
                        FROM company_status_history csh_max
                        GROUP BY id_company) AS csh_max
                    INNER JOIN company_status_history csh ON csh_max.max_id = csh.id
                    INNER JOIN company_status cs ON csh.id_status = cs.id
                  WHERE
                    cs.label IN (:status)
                    AND csh.added BETWEEN :start AND :end';

        return $this->getEntityManager()->getConnection()
            ->executeQuery(
                $query,
                [
                    'status' => $status,
                    'start'  => $start->format('Y-m-d H:i:s'),
                    'end'    => $end->format('Y-m-d H:i:s'),
                ],
                [
                    'status' => Connection::PARAM_STR_ARRAY,
                    'start'  => PDO::PARAM_STR,
                    'end'    => PDO::PARAM_STR,
                ]
            )
            ->fetchAll()
            ;
    }

    /**
     * @param string $siren
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return bool
     */
    public function isProblematicCompany($siren)
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('COUNT(c.idCompany)')
            ->innerJoin(CompanyStatusHistory::class, 'csh', Join::WITH, 'csh.idCompany = c.idCompany')
            ->innerJoin(Projects::class, 'p', Join::WITH, 'p.idCompany = c.idCompany')
            ->innerJoin(ProjectsStatusHistory::class, 'psh', Join::WITH, 'psh.idProject = p.idProject')
            ->innerJoin(CompanyStatus::class, 'cs', Join::WITH, 'cs.id = csh.idStatus')
            ->innerJoin(ProjectsStatus::class, 'ps', Join::WITH, 'ps.idProjectStatus = psh.idProjectStatus')
            ->where('c.siren = :siren')
            ->setParameter('siren', $siren)
            ->andWhere('cs.label != :inBonis')
            ->setParameter('inBonis', CompanyStatus::STATUS_IN_BONIS)
            ->andWhere('ps.status = :projectStatus')
            ->setParameter('projectStatus', ProjectsStatus::STATUS_LOST)
        ;

        return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @param Companies $company
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return int
     */
    public function countDuplicatesByNameAndParent(Companies $company): int
    {
        return $this->createQueryBuilder('co')
            ->select('COUNT(co.idCompany)')
            ->where('LOWER(co.name) LIKE LOWER(:name)')
            ->andWhere('co.idParentCompany = :parent')
            ->setParameter('name', $company->getName())
            ->setParameter('parent', $company->getParent())
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return array
     */
    public function getNotYetMonitoredSirenWithProjects(): array
    {
        $sirenExistSubQuery = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('rdm2')
            ->leftJoin(RiskDataMonitoring::class, 'rdm2')
            ->where('rdm2.siren = co.siren')
        ;

        $queryBuilder = $this->createQueryBuilder('co');
        $queryBuilder->select('DISTINCT co.siren')
            ->innerJoin(Projects::class, 'p', Join::WITH, 'p.idCompany = co.idCompany')
            ->where('p.status > :firstProjectStatus')
            ->andWhere('p.status NOT IN (:excludedStatus)')
            ->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->exists($sirenExistSubQuery->getDQL())))
            ->andWhere('co.siren != \'\'')
            ->andWhere('co.siren IS NOT NULL')
            ->setParameter('excludedStatus', MonitoringCycleManager::LONG_TERM_MONITORING_EXCLUDED_PROJECTS_STATUS)
            ->setParameter('firstProjectStatus', ProjectsStatus::STATUS_CANCELLED)
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function getSirenWithProjectOrCompanyStatusNotToBeMonitored(): array
    {
        $queryBuilder = $this->createQueryBuilder('co');
        $queryBuilder->select('DISTINCT(co.siren) AS siren')
            ->innerJoin(CompanyStatus::class, 'cs', Join::WITH, 'cs.id = co.idStatus')
            ->innerJoin(Projects::class, 'p', Join::WITH, 'co.idCompany = p.idCompany')
            ->innerJoin(RiskDataMonitoring::class, 'rdm', Join::WITH, 'co.siren = rdm.siren')
            ->where('cs.label != :inBonis')
            ->orWhere('p.status IN (:finalStatus)')
            ->setParameter('inBonis', CompanyStatus::STATUS_IN_BONIS)
            ->setParameter('finalStatus', [ProjectsStatus::STATUS_FINISHED, ProjectsStatus::STATUS_CANCELLED])
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @throws DBALException
     *
     * @return array
     */
    public function getSirenWithActiveProjectsAndNoMonitoring()
    {
        $query = '
          SELECT
            DISTINCT(co.siren) AS siren 
          FROM companies co 
            INNER JOIN projects p ON co.id_company = p.id_company 
            INNER JOIN risk_data_monitoring rdm ON co.siren = rdm.siren
            INNER JOIN company_status cs ON co.id_status = cs.id
          WHERE p.status >= :completeRequest 
            AND p.status NOT IN (:projectStatus)
            AND cs.label NOT IN (:companyStatus)
            AND rdm.end <= NOW() 
            AND (SELECT rdm2.end FROM risk_data_monitoring rdm2 WHERE rdm2.siren = co.siren ORDER BY rdm2.start DESC LIMIT 1) IS NOT NULL
            AND co.siren != \'\'
            AND co.siren IS NOT NULL';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, [
                'completeRequest' => ProjectsStatus::STATUS_REQUESTED,
                'projectStatus'   => MonitoringCycleManager::LONG_TERM_MONITORING_EXCLUDED_PROJECTS_STATUS,
                'companyStatus'   => MonitoringCycleManager::LONG_TERM_MONITORING_EXCLUDED_COMPANY_STATUS,
            ], [
                'completeRequest' => PDO::PARAM_INT,
                'projectStatus'   => Connection::PARAM_INT_ARRAY,
                'companyStatus'   => Connection::PARAM_STR_ARRAY,
            ])
            ->fetchAll()
        ;
    }

    /**
     * @param string $provider
     *
     * @throws DBALException
     *
     * @return array
     */
    public function getSirenWithActiveProjectsAndNoMonitoringByProvider(string $provider)
    {
        $query = '
          SELECT
            DISTINCT(co.siren) AS siren 
          FROM companies co 
            INNER JOIN projects p ON co.id_company = p.id_company 
            INNER JOIN risk_data_monitoring rdm ON co.siren = rdm.siren AND rdm.provider = :provider
            INNER JOIN company_status cs ON co.id_status = cs.id
          WHERE p.status >= :completeRequest 
            AND p.status NOT IN (:projectStatus)
            AND cs.label NOT IN (:companyStatus)
            AND rdm.end <= NOW() 
            AND (SELECT rdm2.end FROM risk_data_monitoring rdm2 WHERE rdm2.siren = co.siren AND rdm2.provider = :provider ORDER BY rdm2.start DESC LIMIT 1) IS NOT NULL
            AND co.siren != \'\'
            AND co.siren IS NOT NULL';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, [
                'provider'        => $provider,
                'completeRequest' => ProjectsStatus::STATUS_REQUESTED,
                'projectStatus'   => MonitoringCycleManager::LONG_TERM_MONITORING_EXCLUDED_PROJECTS_STATUS,
                'companyStatus'   => MonitoringCycleManager::LONG_TERM_MONITORING_EXCLUDED_COMPANY_STATUS,
            ], [
                'provider'        => PDO::PARAM_STR,
                'completeRequest' => PDO::PARAM_INT,
                'projectStatus'   => Connection::PARAM_INT_ARRAY,
                'companyStatus'   => Connection::PARAM_STR_ARRAY,
            ])
            ->fetchAll()
        ;
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
            ->where('c.idCompany in (:arrangersToSelect)')
            ->setParameter('arrangersToSelect', array_merge(Companies::COMPANY_ELIGIBLE_ARRANGER, [$currentCompany]))
            ;

        $this->handlerOrderBy($queryBuilder, $orderBy);

        return $queryBuilder;
    }

    /**
     * @param array $orderBy
     *
     * @return QueryBuilder
     */
    public function createEligibleRunQB(array $orderBy = [])
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->where('c.idCompany in (:runsToSelect)')
            ->orWhere('c.parent in (:runsParantToSelect)')
            ->setParameters(['runsToSelect' => Companies::COMPANY_ELIGIBLE_RUN, 'runsParantToSelect' => Companies::COMPANY_SUBSIDIARY_ELIGIBLE_RUN])
            ;

        $this->handlerOrderBy($queryBuilder, $orderBy);

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

        $this->handlerOrderBy($queryBuilder, $orderBy);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array        $orderBy
     */
    private function handlerOrderBy(QueryBuilder $queryBuilder, array $orderBy)
    {
        $aliases = $queryBuilder->getRootAliases();
        if (!isset($aliases[0])) {
            throw new RuntimeException('No alias was set before invoking getRootAlias().');
        }
        $alias = $aliases[0];

        foreach ($orderBy as $sort => $order) {
            $queryBuilder->addOrderBy($alias . '.' . $sort, $order);
        }
    }
}
