<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\ORM\Query\Expr\Join;
use PDO;
use Unilend\librairies\CacheKeys;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Unilend\Bundle\CoreBusinessBundle\Entity\Factures;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;

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
            ['lenderId' => $lenderId, 'status' => \projects_status::REMBOURSEMENT],
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
            'startPeriod'   => \PDO::PARAM_STR,
            'projectStatus' => \PDO::PARAM_INT
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
            $bindTypes['client'] = \PDO::PARAM_INT;
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
}
