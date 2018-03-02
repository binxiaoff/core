<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\librairies\CacheKeys;

class RiskDataMonitoringRepository extends EntityRepository
{
    /**
     * @param \DateTime $start
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCompanyRatingEvents(\DateTime $start): array
    {
        $query = '
          SELECT
          rdm.siren,
          co.id_client_owner,
          co.name,
          p.id_project,
          p.title,
          p.status,
          ps.label,
          rdmt.company_rating,
          cr.value,
          rdma.added AS added,
          (
            SELECT cr_previous.value
            FROM company_rating_history crh_previous
             INNER JOIN company_rating cr_previous ON crh_previous.id_company_rating_history = cr_previous.id_company_rating_history
            WHERE crh_previous.id_company = p.id_company 
             AND cr.type = cr_previous.type 
             AND crh_previous.id_company_rating_history < crh.id_company_rating_history
            ORDER BY crh_previous.added DESC
            LIMIT 1) AS previous_value
        FROM risk_data_monitoring rdm
          INNER JOIN companies co ON rdm.siren = co.siren
          INNER JOIN projects p ON co.id_company = p.id_company
          INNER JOIN projects_status ps ON p.status = ps.status
          INNER JOIN risk_data_monitoring_call_log rdmcl ON rdm.id = rdmcl.id_risk_data_monitoring
          INNER JOIN risk_data_monitoring_assessment rdma ON rdmcl.id = rdma.id_risk_data_monitoring_call_log
          INNER JOIN risk_data_monitoring_type rdmt ON rdma.id_risk_data_monitoring_type = rdmt.id
          INNER JOIN company_rating_history crh ON rdmcl.id_company_rating_history = crh.id_company_rating_history
          INNER JOIN company_rating cr ON crh.id_company_rating_history = cr.id_company_rating_history AND rdmt.company_rating = cr.type
          LEFT JOIN project_eligibility_rule pel ON rdmt.id_project_eligibility_rule = pel.id
        WHERE rdm.end IS NULL
          AND rdma.added >= :date
          AND rdmt.company_rating IS NOT NULL
        GROUP BY p.status, p.id_company, p.id_project, rdmt.id
        ORDER BY rdma.added DESC, p.status DESC, p.id_project DESC';

        $qcProfile = new QueryCacheProfile(CacheKeys::LONG_TIME, md5(__METHOD__));

        return $this->getEntityManager()
            ->getConnection()
            ->executeCacheQuery($query, ['date' => $start->format('Y-m-d H:i:s')], ['date' => \PDO::PARAM_STR], $qcProfile)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getEligibilityEvents(): array
    {
        $query = '
            SELECT
              rdm.siren,
              co.name,
              p.id_project,
              p.title,
              p.status,
              ps.label,
              rdma.added AS added,
              rdma.value
            FROM risk_data_monitoring rdm
              INNER JOIN companies co ON rdm.siren = co.siren
              INNER JOIN projects p ON co.id_company = p.id_company
              INNER JOIN projects_status ps ON p.status = ps.status
              INNER JOIN risk_data_monitoring_call_log rdmcl ON rdm.id = rdmcl.id_risk_data_monitoring
              INNER JOIN risk_data_monitoring_assessment rdma ON rdmcl.id = rdma.id_risk_data_monitoring_call_log
              INNER JOIN risk_data_monitoring_type rdmt ON rdma.id_risk_data_monitoring_type = rdmt.id
              LEFT JOIN project_eligibility_rule pel ON rdmt.id_project_eligibility_rule = pel.id
            WHERE rdm.end IS NULL
              AND rdmt.id_project_eligibility_rule IS NOT NULL
              AND rdma.value != 1
              AND ps.status > ' . ProjectsStatus::COMPLETE_REQUEST . '
            GROUP BY p.status, p.id_company, p.id_project
            ORDER BY rdma.added DESC, p.status DESC, p.id_project DESC';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }
}
