<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use PDO;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Factures;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnilendStats;
use Unilend\librairies\CacheKeys;

class ProjectsRepository extends EntityRepository
{
    /**
     * @param int $lenderId
     *
     * @return int
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

        $sql = 'SELECT p.*, MIN(psh.added) as release_date, p.amount - ROUND(i.montant_ttc / 100, 2) - IF(f.release_funds IS NULL, 0, f.release_funds ) as rest_funds
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
                    $queryBuilder->expr()->notLike('c.telephone', $queryBuilder->expr()->literal(''))
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

        $binds = [
            'startPeriod'   => $datePeriod->getStartDate()->format('Y-m'),
            'projectStatus' => $projectStatus
        ];
        $bindTypes = [
            'startPeriod'   => PDO::PARAM_STR,
            'projectStatus' => PDO::PARAM_INT
        ];
        $query = '
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
            $query .= ' AND id_company_submitter IN (:companies)';
            $binds['companies'] = array_map(function(Companies $company) {
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
     */
    public function findProjectsHavingHadStatusBetweenDates(array $status, \DateTime $start, \DateTime $end)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = 'SELECT
                      *
                    FROM (SELECT MAX(id_project_status_history) AS max_id_projects_status_history
                          FROM projects_status_history psh_max
                          GROUP BY id_project) AS psh_max
                      INNER JOIN projects_status_history psh ON psh_max.max_id_projects_status_history = psh.id_project_status_history
                      INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      INNER JOIN projects p ON p.id_project = psh.id_project
                    WHERE
                      ps.status IN (:status)
                      AND psh.added BETWEEN :start AND :end';

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

    public function getCountProjectsByStatus($status)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('COUNT(p.idProject)')
            ->where('p.status = :status')
            ->setParameter('status', $status);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param bool           $groupFirstYears
     * @param \DateTime|null $date
     *
     * @return array
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
     */
    public function getCountProjectsWithLateRepayments($healthy = true, $groupFirstYears = true)
    {
        $query = '
            SELECT
              COUNT(p.id_project) AS amount,
              (' . $this->getCohortQuery($groupFirstYears) . ') AS cohort
            FROM projects p
              INNER JOIN echeanciers_emprunteur ON echeanciers_emprunteur.id_project = p.id_project 
              INNER JOIN companies c ON c.id_company = p.id_company
              INNER JOIN company_status cs ON cs.id = c.id_status
            WHERE p.status >= ' . ProjectsStatus::REMBOURSEMENT . '
              AND
                (
                SELECT lender_payment_status.status
                FROM echeanciers lender_payment_status
                WHERE lender_payment_status.ordre = echeanciers_emprunteur.ordre
                  AND echeanciers_emprunteur.id_project = lender_payment_status.id_project
                LIMIT 1 ) = 0
              AND
                (
                SELECT lender_payment_date.date_echeance
                FROM echeanciers lender_payment_date
                WHERE lender_payment_date.ordre = echeanciers_emprunteur.ordre
                  AND echeanciers_emprunteur.id_project = lender_payment_date.id_project
                LIMIT 1) < NOW()
              AND IF(
                    cs.label IN (:companyStatus)
                    OR p.status = ' . ProjectsStatus::LOSS . '
                    OR (p.status = ' . ProjectsStatus::PROBLEME . '
                        AND DATEDIFF(NOW(), (
                                            SELECT psh2.added
                                            FROM projects_status_history psh2
                                              INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                                            WHERE ps2.status = ' . ProjectsStatus::PROBLEME . '
                                              AND psh2.id_project = echeanciers_emprunteur.id_project
                                            ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                                            LIMIT 1)) > ' . UnilendStats::DAYS_AFTER_LAST_PROBLEM_STATUS_FOR_STATISTIC_LOSS . '), TRUE, FALSE) = :healthy
            GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()->executeQuery(
            $query,
            ['companyStatus' => [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION], 'healthy' => $healthy],
            ['companyStatus' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY, 'healthy' => \PDO::PARAM_BOOL]
        );
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param bool            $weighted
     * @param bool            $groupFirstYears
     * @param \DateTime|null  $date
     *
     * @return array
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
     * @param bool            $weighted
     * @param \DateTime|null  $date
     *
     * @return bool|string
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
}
