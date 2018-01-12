<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use PDO;
use Psr\Log\InvalidArgumentException;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, Companies, CompanyStatus, Echeanciers, EcheanciersEmprunteur, Factures, OperationType, Partner, Projects, ProjectsStatus, UnilendStats
};
use Unilend\librairies\CacheKeys;

class ProjectsRepository extends EntityRepository
{
    /**
     * @param int $lenderId
     *
     * @return int
     * @throws \Doctrine\DBAL\Cache\CacheException
     */
    public function countCompaniesLenderInvestedIn($lenderId)
    {
        $query = '
            SELECT COUNT(DISTINCT p.id_company)
            FROM projects p
            INNER JOIN loans l ON p.id_project = l.id_project
            WHERE p.status >= :status AND l.id_lender = :lenderId';

        $statement = $this->getEntityManager()->getConnection()->executeCacheQuery(
            $query,
            ['lenderId' => $lenderId, 'status' => ProjectsStatus::REMBOURSEMENT],
            ['lenderId' => PDO::PARAM_INT, 'status' => PDO::PARAM_INT],
            new QueryCacheProfile(CacheKeys::SHORT_TIME, md5(__METHOD__))
        );
        $result    = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return (int) current($result[0]);
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return Projects[]
     */
    public function findPartiallyReleasedProjects(\DateTime $dateTime)
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('UnilendCoreBusinessBundle:Projects', 'p');

        $sql = 'SELECT p.*, MIN(psh.added) AS release_date, p.amount - ROUND(i.montant_ttc / 100, 2) - IF(f.release_funds IS NULL, 0, f.release_funds ) AS rest_funds
                FROM projects p
                  INNER JOIN projects_status_history psh ON psh.id_project = p.id_project
                  INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
                  INNER JOIN factures i ON i.id_project = p.id_project
                  LEFT JOIN (SELECT o.id_project, SUM(amount) AS release_funds
                              FROM operation o
                                INNER JOIN operation_type ot ON o.id_type = ot.id
                              WHERE ot.label = :borrower_withdraw
                              GROUP BY o.id_project) f ON f.id_project = p.id_project
                WHERE ps.status = :repayment
                      AND i.type_commission = :funds_commission
                GROUP BY p.id_project
                HAVING rest_funds > 0 AND release_date <= :date';

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameters([
            'borrower_withdraw' => OperationType::BORROWER_WITHDRAW,
            'repayment'         => ProjectsStatus::REMBOURSEMENT,
            'funds_commission'  => Factures::TYPE_COMMISSION_FUNDS,
            'date'              => $dateTime,
        ]);

        return $query->getResult();
    }

    /**
     * @param array $companies
     *
     * @return Projects[]
     */
    public function getPartnerProspects(array $companies)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:Companies', 'co', Join::WITH, 'p.idCompany = co.idCompany')
            ->innerJoin('UnilendCoreBusinessBundle:Clients', 'c', Join::WITH, 'co.idClientOwner = c.idClient')
            ->where('p.idCompanySubmitter IN (:userCompanies)')
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('p.status', ':projectStatus'),
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('p.status', ':noAutoEvaluationStatus'),
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->like('c.telephone', $queryBuilder->expr()->literal('')),
                        $queryBuilder->expr()->isNull('c.telephone')
                    )
                )
            ))
            ->setParameter('userCompanies', $companies)
            ->setParameter('projectStatus', ProjectsStatus::SIMULATION)
            ->setParameter('noAutoEvaluationStatus', ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION)
            ->orderBy('p.added', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array $companies
     *
     * @return Projects[]
     */
    public function getPartnerProjects(array $companies)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:Companies', 'co', Join::WITH, 'p.idCompany = co.idCompany')
            ->innerJoin('UnilendCoreBusinessBundle:Clients', 'c', Join::WITH, 'co.idClientOwner = c.idClient')
            ->where('p.idCompanySubmitter IN (:userCompanies)')
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gte('p.status', ':projectStatus'),
                    $queryBuilder->expr()->notIn('p.status', ':excludedStatus')
                ),
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq('p.status', ':noAutoEvaluationStatus'),
                    $queryBuilder->expr()->notLike('c.telephone', $queryBuilder->expr()->literal('')),
                    $queryBuilder->expr()->isNotNull('c.telephone')
                )
            ))
            ->setParameter('userCompanies', $companies)
            ->setParameter('projectStatus', ProjectsStatus::INCOMPLETE_REQUEST)
            ->setParameter('excludedStatus', [ProjectsStatus::ABANDONED, ProjectsStatus::COMMERCIAL_REJECTION, ProjectsStatus::ANALYSIS_REJECTION, ProjectsStatus::COMITY_REJECTION])
            ->setParameter('noAutoEvaluationStatus', ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION)
            ->orderBy('p.added', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array $companies
     *
     * @return Projects[]
     */
    public function getPartnerAbandoned(array $companies)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder
            ->where('p.idCompanySubmitter IN (:userCompanies)')
            ->andWhere($queryBuilder->expr()->in('p.status', ':projectStatus'))
            ->setParameter('userCompanies', $companies)
            ->setParameter('projectStatus', [ProjectsStatus::ABANDONED])
            ->orderBy('p.added', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array $companies
     *
     * @return Projects[]
     */
    public function getPartnerRejected(array $companies)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder
            ->where('p.idCompanySubmitter IN (:userCompanies)')
            ->andWhere($queryBuilder->expr()->in('p.status', ':projectStatus'))
            ->setParameter('userCompanies', $companies)
            ->setParameter('projectStatus', [ProjectsStatus::COMMERCIAL_REJECTION, ProjectsStatus::ANALYSIS_REJECTION, ProjectsStatus::COMITY_REJECTION])
            ->orderBy('p.added', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int         $projectStatus
     * @param \DatePeriod $datePeriod
     * @param array|null  $companies
     * @param int|null    $client
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getMonthlyStatistics($projectStatus, \DatePeriod $datePeriod, array $companies = null, $client = null)
    {
        $statistics = [];

        foreach ($datePeriod as $month) {
            $statistics[$month->format('Y-m')] = [
                'count' => 0,
                'sum'   => 0,
                'month' => $month->format('Y-m')
            ];
        }

        $binds     = [
            'startPeriod'   => $datePeriod->getStartDate()->format('Y-m'),
            'projectStatus' => $projectStatus
        ];
        $bindTypes = [
            'startPeriod'   => PDO::PARAM_STR,
            'projectStatus' => PDO::PARAM_INT
        ];
        $query     = '
            SELECT COUNT(*) AS count,
                IFNULL(SUM(p.amount), 0) AS sum,
                LEFT(history.transition, 7) AS month
            FROM projects p
            INNER JOIN (
                SELECT psh.id_project, MIN(psh.added) AS transition
                FROM projects_status_history psh
                INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = :projectStatus
                GROUP BY psh.id_project
            ) history ON p.id_project = history.id_project
            WHERE history.transition > :startPeriod';

        if (null !== $companies) {
            $query                  .= ' AND id_company_submitter IN (:companies)';
            $binds['companies']     = array_map(function (Companies $company) {
                return $company->getIdCompany();
            }, $companies);
            $bindTypes['companies'] = Connection::PARAM_INT_ARRAY;
        }

        if (null !== $client) {
            $query               .= ' AND id_client_submitter = :client';
            $binds['client']     = $client;
            $bindTypes['client'] = PDO::PARAM_INT;
        }

        $query .= ' GROUP BY month';

        $result = $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, $binds, $bindTypes)->fetchAll();

        foreach ($result as $month) {
            $statistics[$month['month']] = $month;
        }

        return $statistics;
    }

    /**
     * @param int       $status
     * @param \DateTime $from
     *
     * @return Projects[]
     */
    public function getProjectsByStatusFromDate($status, \DateTime $from)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', $status)
            ->andWhere('p.added >= :from')
            ->setParameter('from', $from);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $siren
     * @param array  $projectStatus
     * @param array  $companyStatusLabel
     *
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCountProjectsBySirenAndNotInStatus($siren, array $projectStatus, array $companyStatusLabel)
    {
        $companyStatusId = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('cs.id')
            ->from('UnilendCoreBusinessBundle:CompanyStatus', 'cs')
            ->where('cs.label IN (:companyStatusLabel)')
            ->setParameter('companyStatusLabel', $companyStatusLabel, Connection::PARAM_STR_ARRAY)
            ->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);

        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->select('COUNT(p.idProject)')
            ->innerJoin('UnilendCoreBusinessBundle:Companies', 'co', Join::WITH, 'co.idCompany = p.idCompany')
            ->where('p.status NOT IN (:projectStatus)')
            ->andWhere('co.siren = :siren')
            ->andWhere('co.idStatus NOT IN (:companyStatusId)')
            ->setParameter('siren', $siren)
            ->setParameter('projectStatus', $projectStatus)
            ->setParameter('companyStatusId', $companyStatusId);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param $siren
     *
     * @return Projects[]
     */
    public function findBySiren($siren)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->innerJoin('UnilendCoreBusinessBundle:Companies', 'c', Join::WITH, 'p.idCompany = c.idCompany')
            ->where('c.siren = :siren')
            ->setParameter('siren', $siren);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string    $select
     * @param \DateTime $start
     * @param \DateTime $end
     * @param int       $projectStatus
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getIndicatorBetweenDates($select, \DateTime $start, \DateTime $end, $projectStatus)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = 'SELECT ' . $select . ' 
                  FROM (SELECT MIN(id_project_status_history), added, id_project 
                        FROM projects_status_history psh
                          INNER JOIN projects_status ps  ON psh.id_project_status = ps.id_project_status
                        WHERE ps.status = :status
                        GROUP BY id_project) AS t
                      INNER JOIN projects p ON p.id_project = t.id_project
                    WHERE t.added BETWEEN :start AND :end';

        $result = $this->getEntityManager()->getConnection()->executeQuery($query, [
            'status' => $projectStatus,
            'start'  => $start->format('Y-m-d H:i:s'),
            'end'    => $end->format('Y-m-d H:i:s')
        ])->fetchAll(PDO::FETCH_ASSOC);

        return $result[0];
    }

    /**
     * @param array     $status
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findProjectsHavingHadStatusBetweenDates(array $status, \DateTime $start, \DateTime $end)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = 'SELECT *
                    FROM projects_status_history psh
                      INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      INNER JOIN projects p ON p.id_project = psh.id_project
                    WHERE
                      ps.status IN (:status)
                      AND psh.added BETWEEN :start AND :end
                    GROUP BY p.id_project';

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query, [
                'status' => $status,
                'start'  => $start->format('Y-m-d H:i:s'),
                'end'    => $end->format('Y-m-d H:i:s')
            ], [
                'status' => Connection::PARAM_INT_ARRAY,
                'start'  => PDO::PARAM_STR,
                'end'    => PDO::PARAM_STR
            ])->fetchAll();

        return $result;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findProjectsWithDebtCollectionMissionBetweenDates(\DateTime $start, \DateTime $end)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = 'SELECT
                      *
                    FROM
                      projects p
                      INNER JOIN companies c ON c.id_company = p.id_company
                      INNER JOIN company_status cs ON cs.id = c.id_status
                      WHERE p.id_project IN
                            (SELECT DISTINCT dcm.id_project FROM debt_collection_mission dcm WHERE dcm.added BETWEEN :start AND :end)
                      AND cs.label = :inBonis';

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query,
                [
                    'inBonis' => CompanyStatus::STATUS_IN_BONIS,
                    'start'   => $start->format('Y-m-d H:i:s'),
                    'end'     => $end->format('Y-m-d H:i:s')
                ], [
                    'inBonis' => PDO::PARAM_STR,
                    'start'   => PDO::PARAM_STR,
                    'end'     => PDO::PARAM_STR
                ])->fetchAll();

        return $result;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findProjectsHavingHadCompanyStatusInCollectiveProceeding(\DateTime $start, \DateTime $end)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = 'SELECT *
                    FROM (SELECT MAX(id) AS max_id
                          FROM company_status_history csh_max
                          GROUP BY id_company) AS csh_max
                      INNER JOIN company_status_history csh ON csh_max.max_id = csh.id
                      INNER JOIN company_status cs ON csh.id_status = cs.id
                      INNER JOIN projects p ON p.id_company = csh.id_company
                    WHERE
                      p.status IN (:projectStatus)
                      AND cs.label IN (:collectiveProceeding)
                      AND csh.added BETWEEN :start AND :end';

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query, [
                'collectiveProceeding' => [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION],
                'projectStatus'        => [ProjectsStatus::PROBLEME, ProjectsStatus::LOSS],
                'start'                => $start->format('Y-m-d H:i:s'),
                'end'                  => $end->format('Y-m-d H:i:s')
            ], [
                'collectiveProceeding' => Connection::PARAM_STR_ARRAY,
                'projectStatus'        => Connection::PARAM_INT_ARRAY,
                'start'                => PDO::PARAM_STR,
                'end'                  => PDO::PARAM_STR
            ])->fetchAll();

        return $result;
    }

    /**
     * @param \DateTime $end
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findProjectsInRepaymentAtDate(\DateTime $end)
    {
        $end->setTime(23, 59, 59);

        $query = 'SELECT p.*
                    FROM projects p
                      INNER JOIN projects_status_history psh ON psh.id_project = p.id_project
                      INNER JOIN (SELECT MAX(id_project_status_history) AS max_id_project_status_history
                                  FROM projects_status_history psh_max
                                  GROUP BY id_project) t ON t.max_id_project_status_history = psh.id_project_status_history
                      INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE psh.added <= :end AND ps.status >= ' . ProjectsStatus::REMBOURSEMENT . '
                    AND ps.status NOT IN (:projectStatus)';

        $result = $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                $query,
                [
                    'end'           => $end->format('Y-m-d H:i:s'),
                    'projectStatus' => [ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE, ProjectsStatus::LOSS]
                ], [
                    'end'           => PDO::PARAM_STR,
                    'projectStatus' => Connection::PARAM_INT_ARRAY
                ]
            )->fetchAll();

        return $result;
    }

    /**
     * @param string   $search
     * @param int|null $limit
     *
     * @return array
     */
    public function findByAutocomplete($search, $limit = null)
    {
        $search       = trim(filter_var($search, FILTER_SANITIZE_STRING));
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p.idProject, p.title, p.amount, p.period, co.name, co.siren, ps.label')
            ->innerJoin('UnilendCoreBusinessBundle:Companies', 'co', Join::WITH, 'co.idCompany = p.idCompany')
            ->innerJoin('UnilendCoreBusinessBundle:ProjectsStatus', 'ps', Join::WITH, 'ps.status = p.status')
            ->where('p.title LIKE :searchLike')
            ->orWhere('co.name LIKE :searchLike')
            ->setParameter('searchLike', '%' . $search . '%')
            ->orderBy('p.added', 'DESC');

        if (filter_var($search, FILTER_VALIDATE_INT)) {
            $queryBuilder
                ->orWhere('p.idProject = :searchInt')
                ->orWhere('co.siren LIKE :searchIntLike')
                ->setParameter('searchInt', $search)
                ->setParameter('searchIntLike', $search . '%');
        }

        if (is_int($limit)) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int|Companies $companyId
     *
     * @return Projects[]
     */
    public function findFundedButNotRepaidProjectsByCompany($companyId)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.idCompany = :companyId')
            ->setParameter('companyId', $companyId)
            ->andWhere('p.status IN (:projectStatus)')
            ->setParameter('projectStatus', [ProjectsStatus::REMBOURSEMENT, ProjectsStatus::PROBLEME, ProjectsStatus::LOSS]);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get all projects having a late payment schedule
     *
     * @return array
     */
    public function getProjectsWithLateRepayments()
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select('p.idProject, ps.label AS projectStatusLabel')
            ->innerJoin('UnilendCoreBusinessBundle:EcheanciersEmprunteur', 'ee', Join::WITH, 'ee.idProject = p.idProject')
            ->innerJoin('UnilendCoreBusinessBundle:ProjectsStatus', 'ps', Join::WITH, 'p.status = ps.status')
            ->where('ee.dateEcheanceEmprunteur < :today')
            ->setParameter('today', (new \DateTime())->format('Y-m-d 00:00:00'))
            ->andWhere('ee.statusEmprunteur IN (:paymentStatus)')
            ->setParameter('paymentStatus', [EcheanciersEmprunteur::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PARTIALLY_PAID])
            ->andWhere('p.status IN (:projectStatus)')
            ->setParameter('projectStatus', [ProjectsStatus::REMBOURSEMENT, ProjectsStatus::PROBLEME])
            ->groupBy('p.idProject');

        return $queryBuilder->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

    /**
     * @param array  $status
     * @param string $siren
     *
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCountProjectsByStatusAndSiren(array $status, $siren)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.idProject)')
            ->innerJoin('UnilendCoreBusinessBundle:Companies', 'co', Join::WITH, 'co.idCompany = p.idCompany')
            ->where('p.status IN (:status)')
            ->andWhere('co.siren = :siren')
            ->setParameter('status', $status)
            ->setParameter('siren', $siren);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param bool           $groupFirstYears
     * @param \DateTime|null $date
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNonWeightedAverageInterestRateByCohortUntil($groupFirstYears = true, \DateTime $date = null)
    {
        $bind  = [];
        $query = '
            SELECT
              AVG(l.rate) AS amount, ( ' . $this->getCohortQuery($groupFirstYears) . ' ) AS cohort
            FROM projects p
              INNER JOIN loans l ON p.id_project = l.id_project
            WHERE p.status >= ' . ProjectsStatus::REMBOURSEMENT;

        if (null !== $date) {
            $date->setTime(23, 59, 59);
            $bind['end'] = $date->format('Y-m-d H:i:s');

            $query .= '
                AND (
                      SELECT added
                      FROM projects_status_history psh
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                        AND psh.id_project = l.id_project
                      ORDER BY added ASC
                      LIMIT 1
                    ) <= :end';
        }

        $query .= ' GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, $bind);
        $result    = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param \DateTime|null $date
     *
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNonWeightedAverageInterestRateUntil(\DateTime $date = null)
    {
        $bind  = [];
        $query = '
            SELECT
              AVG(l.rate) AS averageRate
            FROM projects p
              INNER JOIN loans l ON p.id_project = l.id_project
            WHERE p.status >= ' . ProjectsStatus::REMBOURSEMENT;

        if (null !== $date) {
            $date->setTime(23, 59, 59);
            $bind['end'] = $date->format('Y-m-d H:i:s');

            $query .= '
                AND (
                      SELECT added
                      FROM projects_status_history psh
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                        AND psh.id_project = l.id_project
                      ORDER BY added ASC
                      LIMIT 1
                    ) <= :end';
        }

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, $bind);
        $result    = $statement->fetchColumn();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param bool           $groupFirstYears
     * @param \DateTime|null $date
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getWeightedAverageInterestRateByCohortUntil($groupFirstYears = true, \DateTime $date = null)
    {
        $bind      = [];
        $baseQuery = '
            SELECT
              l.rate,
              l.amount,
              l.id_project
            FROM projects p
              INNER JOIN loans l ON p.id_project = l.id_project
            WHERE p.status >= ' . ProjectsStatus::REMBOURSEMENT;

        if (null !== $date) {
            $date->setTime(23, 59, 59);
            $bind['end'] = $date->format('Y-m-d H:i:s');

            $baseQuery .= '
                AND (
                      SELECT added
                      FROM projects_status_history psh
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                        AND psh.id_project = l.id_project
                      ORDER BY added ASC
                      LIMIT 1
                    ) <= :end';
        }

        $query = 'SELECT SUM(amount * rate) / SUM(amount) AS amount, ( ' . $this->getCohortQuery($groupFirstYears) . ' ) AS cohort
                  FROM ( ' . $baseQuery . ') AS p
                  GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, $bind);
        $result    = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param bool           $groupFirstYears
     * @param \DateTime|null $date
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNonWeightedAveragePeriodByCohortUntil($groupFirstYears = true, \DateTime $date = null)
    {
        $bind  = [];
        $query = '
            SELECT
              AVG(p.period) AS amount, ( ' . $this->getCohortQuery($groupFirstYears) . ' ) AS cohort
            FROM projects p
            WHERE p.status >= ' . ProjectsStatus::REMBOURSEMENT;

        if (null !== $date) {
            $date->setTime(23, 59, 59);
            $bind['end'] = $date->format('Y-m-d H:i:s');

            $query .= '
                AND (
                      SELECT added
                      FROM projects_status_history psh
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                        AND psh.id_project = p.id_project
                      ORDER BY added ASC
                      LIMIT 1
                    ) <= :end';
        }

        $query .= ' GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, $bind);
        $result    = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param \DateTime|null $date
     *
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNonWeightedAveragePeriodUntil(\DateTime $date = null)
    {
        $bind  = [];
        $query = '
          SELECT
            AVG(p.period) AS averagePeriod
          FROM projects p
          WHERE p.status >= ' . ProjectsStatus::REMBOURSEMENT;

        if (null !== $date) {
            $date->setTime(23, 59, 59);
            $bind['end'] = $date->format('Y-m-d H:i:s');

            $query .= '
                AND (
                      SELECT added
                      FROM projects_status_history psh
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                        AND psh.id_project = p.id_project
                      ORDER BY added ASC
                      LIMIT 1
                    ) <= :end';
        }

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, $bind);
        $result    = $statement->fetchColumn();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param bool           $groupFirstYears
     * @param \DateTime|null $date
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getWeightedAveragePeriodByCohortUntil($groupFirstYears = true, \DateTime $date = null)
    {
        $bind      = [];
        $baseQuery = '
            SELECT
              p.period,
              p.amount,
              p.id_project
            FROM projects p
            WHERE p.status >= ' . ProjectsStatus::REMBOURSEMENT;

        if (null !== $date) {
            $date->setTime(23, 59, 59);
            $bind['end'] = $date->format('Y-m-d H:i:s');

            $baseQuery .= '
                AND (
                      SELECT added
                      FROM projects_status_history psh
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                        AND psh.id_project = p.id_project
                      ORDER BY added ASC
                      LIMIT 1
                    ) <= :end';
        }

        $query = '
            SELECT SUM(amount * period) / SUM(amount) AS amount, ( ' . $this->getCohortQuery($groupFirstYears) . ' ) AS cohort
            FROM ( ' . $baseQuery . ') AS p
            GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, $bind);
        $result    = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param \DateTime|null $date
     *
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getWeightedAveragePeriodUntil(\DateTime $date = null)
    {
        $bind      = [];
        $baseQuery = '
            SELECT
              p.period,
              p.amount,
              p.id_project
            FROM projects p
            WHERE p.status >= ' . ProjectsStatus::REMBOURSEMENT;

        if (null !== $date) {
            $date->setTime(23, 59, 59);
            $bind['end'] = $date->format('Y-m-d H:i:s');

            $baseQuery .= '
                AND (
                      SELECT added
                      FROM projects_status_history psh
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                        AND psh.id_project = p.id_project
                      ORDER BY added ASC
                      LIMIT 1
                    ) <= :end';
        }

        $query = '
            SELECT SUM(amount * period) / SUM(amount) AS averagePeriod
            FROM ( ' . $baseQuery . ') AS p';

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, $bind);
        $result    = $statement->fetchColumn();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param bool $groupFirstYears
     *
     * @return string
     */
    private function getCohortQuery($groupFirstYears)
    {
        if ($groupFirstYears) {
            $cohortSelect = 'CASE LEFT(psh.added, 4)
                                WHEN 2013 THEN "2013-2014"
                                WHEN 2014 THEN "2013-2014"
                                ELSE LEFT(psh.added, 4)
                             END';
        } else {
            $cohortSelect = 'LEFT(psh.added, 4)';
        }

        return 'SELECT ' . $cohortSelect . ' AS date_range
                FROM projects_status_history psh
                  INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . ' AND p.id_project = psh.id_project
                GROUP BY psh.id_project';
    }

    /**
     * @param bool $groupFirstYears
     * @param bool $healthy
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCountProjectsWithLateRepayments($healthy = true, $groupFirstYears = true)
    {
        $query = '
            SELECT
                COUNT(DISTINCT p.id_project) AS amount,
                (' . $this->getCohortQuery($groupFirstYears) . ') AS cohort
            FROM projects p
            INNER JOIN echeanciers_emprunteur ON echeanciers_emprunteur.id_project = p.id_project 
            INNER JOIN companies c ON c.id_company = p.id_company
            INNER JOIN company_status cs ON cs.id = c.id_status
            WHERE p.status >= ' . ProjectsStatus::REMBOURSEMENT . '
                AND (
                    SELECT lender_payment_status.status
                    FROM echeanciers lender_payment_status
                    WHERE lender_payment_status.ordre = echeanciers_emprunteur.ordre AND echeanciers_emprunteur.id_project = lender_payment_status.id_project
                    LIMIT 1 
                ) = ' . Echeanciers::STATUS_PENDING . '
                AND (
                    SELECT lender_payment_date.date_echeance
                    FROM echeanciers lender_payment_date
                    WHERE lender_payment_date.ordre = echeanciers_emprunteur.ordre AND echeanciers_emprunteur.id_project = lender_payment_date.id_project
                    LIMIT 1
                ) < NOW()
                AND IF((
                    cs.label IN (:companyStatus)
                    OR p.status = ' . ProjectsStatus::LOSS . '
                    OR (
                        p.status = ' . ProjectsStatus::PROBLEME . '
                        AND DATEDIFF(NOW(), (
                            SELECT psh2.added
                            FROM projects_status_history psh2
                            INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                            WHERE ps2.status = ' . ProjectsStatus::PROBLEME . ' AND psh2.id_project = echeanciers_emprunteur.id_project
                            ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                            LIMIT 1
                        )) > ' . UnilendStats::DAYS_AFTER_LAST_PROBLEM_STATUS_FOR_STATISTIC_LOSS . '
                    )
                ), FALSE, TRUE) = :healthy
            GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()->executeQuery(
            $query,
            ['companyStatus' => [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION], 'healthy' => $healthy],
            ['companyStatus' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY, 'healthy' => \PDO::PARAM_BOOL]
        );
        $result    = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param bool           $weighted
     * @param bool           $groupFirstYears
     * @param \DateTime|null $date
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAverageLoanAgeByCohortUntil($weighted, $groupFirstYears, \DateTime $date = null)
    {
        $bind      = [];
        $baseQuery = '
            SELECT
              TIMESTAMPDIFF(MONTH, (
                SELECT added 
                FROM projects_status_history
                  INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                WHERE status = ' . ProjectsStatus::REMBOURSEMENT . '
                 AND projects_status_history.id_project = p.id_project
                ORDER BY added, id_project_status_history ASC
                LIMIT 1
              ), NOW()) AS age,
              p.period,
              p.id_project,
              p.amount,
              (' . $this->getCohortQuery($groupFirstYears) . ') AS cohort
            FROM projects p
            WHERE p.status >= ' . ProjectsStatus::REMBOURSEMENT . '
            GROUP BY p.id_project';

        if (null !== $date) {
            $date->setTime(23, 59, 59);
            $bind['end'] = $date->format('Y-m-d H:i:s');

            $baseQuery .= '
                AND (
                      SELECT added
                      FROM projects_status_history psh
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                        AND psh.id_project = p.id_project
                      ORDER BY added ASC
                      LIMIT 1
                    ) <= :end';
        }

        if ($weighted) {
            $query = '
                SELECT SUM(t.amount * t.age) / SUM(t.amount) AS amount, t.cohort
                FROM ( ' . $baseQuery . ' ) AS t
                GROUP BY t.cohort';
        } else {
            $query = '
                SELECT AVG(t.age) AS amount, t.cohort
                FROM ( ' . $baseQuery . ' ) AS t
                GROUP BY t.cohort';
        }

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, $bind);
        $result    = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param bool           $weighted
     * @param \DateTime|null $date
     *
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getAverageLoanAgeUntil($weighted, \DateTime $date = null)
    {
        $bind      = [];
        $baseQuery = '
            SELECT
              TIMESTAMPDIFF(MONTH, (
                SELECT added 
                FROM projects_status_history
                  INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                WHERE status = ' . ProjectsStatus::REMBOURSEMENT . '
                 AND projects_status_history.id_project = p.id_project
                ORDER BY added, id_project_status_history ASC
                LIMIT 1
              ), NOW()) AS age,
              p.period,
              p.id_project,
              p.amount
             FROM projects p
             WHERE p.status >= ' . ProjectsStatus::REMBOURSEMENT . '
             GROUP BY p.id_project';

        if (null !== $date) {
            $date->setTime(23, 59, 59);
            $bind['end'] = $date->format('Y-m-d H:i:s');

            $baseQuery .= '
                AND (
                      SELECT added
                      FROM projects_status_history psh
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                        AND psh.id_project = p.id_project
                      ORDER BY added ASC
                      LIMIT 1
                    ) <= :end';
        }

        if ($weighted) {
            $query = 'SELECT SUM(t.amount * t.age) / SUM(t.amount) AS amount FROM ( ' . $baseQuery . ') AS t';
        } else {
            $query = 'SELECT AVG(t.age) FROM ( ' . $baseQuery . ') AS t';
        }

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, $bind);
        $result    = $statement->fetchColumn();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param string $siren
     *
     * @return mixed
     */
    public function findProjectsBySiren($siren)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->innerJoin('UnilendCoreBusinessBundle:Companies', 'co', Join::WITH, 'co.idCompany = p.idCompany')
            ->where('co.siren = :siren')
            ->setParameter('siren', $siren);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function getProjectsInDebt()
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->select('p.idProject')
            ->innerJoin('UnilendCoreBusinessBundle:Companies', 'c', Join::WITH, 'c.idCompany = p.idCompany')
            ->innerJoin('UnilendCoreBusinessBundle:CompanyStatus', 'cs', Join::WITH, 'c.idStatus = cs.id')
            ->where('cs.label in (:problemStatus)')
            ->setParameter('problemStatus', [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION]);

        return array_column($queryBuilder->getQuery()->getArrayResult(), 'idProject');
    }

    /**
     * @param Clients|Companies|Partner $submitter
     *
     * @return array
     * @throws InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getSubmitterKPI($submitter) : array
    {
        $query = '
            SELECT
              IFNULL(SUM(IF(p.status >= :sentStatus, 1, 0)), 0) AS sentCount,
              IFNULL(SUM(IF(p.status >= :sentStatus, p.amount, 0)), 0) AS sentAmount,
              IFNULL(SUM(IF(p.status >= :repaymentStatus, 1, 0)), 0) AS repaymentCount,
              IFNULL(SUM(IF(p.status >= :repaymentStatus, p.amount, 0)), 0) AS repaymentAmount,
              IFNULL(ROUND(SUM(IF(pb.id_project IS NULL, 0, 1)) / SUM(IF(p.status >= :repaymentStatus, 1, 0)) * 100), 0) AS problemRate,
              IFNULL(ROUND(SUM(IF(p.status IN (:rejectionStatus), 1, 0)) / COUNT(p.id_project) * 100), 0) AS rejectionRate
            FROM projects p
              LEFT JOIN (
                SELECT id_project
                FROM projects_status_history psh
                  INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status IN (:problemStatus)
                GROUP BY psh.id_project
              ) pb ON p.id_project = pb.id_project';

        if ($submitter instanceof Clients) {
            $submitterId = $submitter->getIdClient();
            $query       .= '
                WHERE p.id_client_submitter = :submitterId';
        } elseif ($submitter instanceof Companies) {
            $submitterId = $submitter->getIdCompany();
            $query       .= '
                WHERE p.id_company_submitter = :submitterId';
        } elseif ($submitter instanceof Partner) {
            $submitterId = $submitter->getIdCompany()->getIdCompany();
            $query       .= '
                WHERE p.id_company_submitter = :submitterId OR p.id_company_submitter IN (SELECT id_company FROM companies WHERE id_parent_company = :submitterId)';
        } else {
            throw new InvalidArgumentException('One and only one of the parameters must be set');
        }

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery(
                $query, [
                'sentStatus'      => ProjectsStatus::COMPLETE_REQUEST,
                'repaymentStatus' => ProjectsStatus::REMBOURSEMENT,
                'rejectionStatus' => [ProjectsStatus::NOT_ELIGIBLE, ProjectsStatus::COMMERCIAL_REJECTION, ProjectsStatus::ANALYSIS_REJECTION, ProjectsStatus::COMITY_REJECTION],
                'problemStatus'   => [ProjectsStatus::PROBLEME, ProjectsStatus::LOSS],
                'submitterId'     => $submitterId
            ], [
                'sentStatus'      => PDO::PARAM_INT,
                'repaymentStatus' => PDO::PARAM_INT,
                'rejectionStatus' => Connection::PARAM_INT_ARRAY,
                'problemStatus'   => Connection::PARAM_INT_ARRAY,
                'submitterId'     => PDO::PARAM_INT
            ])->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param Clients|Companies|Partner $submitter
     *
     * @return array
     * @throws InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getSubmitterProjectsCountSortedByStatus($submitter) : array
    {
        $query = '
            SELECT
              COUNT(*) AS statusCount,
              ps.label AS statusLabel,
              ps.status AS status
            FROM projects p
            INNER JOIN projects_status ps ON p.status = ps.status';

        if ($submitter instanceof Clients) {
            $submitterId = $submitter->getIdClient();
            $query       .= '
                WHERE p.id_client_submitter = :submitterId';
        } elseif ($submitter instanceof Companies) {
            $submitterId = $submitter->getIdCompany();
            $query       .= '
                WHERE p.id_company_submitter = :submitterId';
        } elseif ($submitter instanceof Partner) {
            $submitterId = $submitter->getId();
            $query       .= '
                WHERE p.id_partner = :submitterId';
        } else {
            throw new InvalidArgumentException('Unknown submitter type ' . get_class($submitter));
        }

        $query .= '
            GROUP BY p.status
            ORDER BY p.status ASC';

        $result = $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['submitterId' => $submitterId])
            ->fetchAll(PDO::FETCH_ASSOC);

        return array_combine(array_column($result, 'status'), $result);
    }

    /**
     * @param Clients|Companies|Partner $submitter
     * @param int                       $status
     *
     * @return Projects[]
     */
    public function findSubmitterProjectsByStatus($submitter, int $status) : array
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('UnilendCoreBusinessBundle:Projects', 'p');

        $query = '
            SELECT * 
            FROM projects p
            WHERE p.status = :status';

        if ($submitter instanceof Clients) {
            $submitterId = $submitter->getIdClient();
            $query       .= ' AND p.id_client_submitter = :submitter';
        } elseif ($submitter instanceof Companies) {
            $submitterId = $submitter->getIdCompany();
            $query       .= ' AND p.id_company_submitter = :submitter';
        } elseif ($submitter instanceof Partner) {
            $submitterId = $submitter->getId();
            $query       .= ' AND p.id_partner = :submitter';
        } else {
            throw new InvalidArgumentException('Unknown submitter type ' . get_class($submitter));
        }

        $nativeQuery = $this->_em->createNativeQuery($query, $rsm);
        $nativeQuery->setParameters([
            'status'    => $status,
            'submitter' => $submitterId
        ]);

        return $nativeQuery->getResult();
    }

    /**
     * @param int       $status
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function countProjectInStatusByMonthAndPartner(int $status, \DateTime $start, \DateTime $end) : array
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->select('DATE_FORMAT(psh.added,\'%m/%Y\') AS month, c.name as partner, count(DISTINCT p.idProject) as number')
            ->innerJoin('UnilendCoreBusinessBundle:ProjectsStatusHistory', 'psh', Join::WITH, 'psh.idProject = p.idProject')
            ->innerJoin('UnilendCoreBusinessBundle:ProjectsStatus', 'ps', Join::WITH, 'ps.idProjectStatus = psh.idProjectStatus')
            ->innerJoin('UnilendCoreBusinessBundle:Partner', 'pa', Join::WITH, 'pa.id = p.idPartner')
            ->innerJoin('UnilendCoreBusinessBundle:Companies', 'c', Join::WITH, 'c.idCompany = pa.idCompany')
            ->where('ps.status = :status')
            ->andWhere('psh.added BETWEEN :start AND :end')
            ->groupBy('month, p.idPartner')
            ->orderBy('psh.added')
            ->setParameter('status', $status)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        return $queryBuilder->getQuery()->getArrayResult();
    }
}
