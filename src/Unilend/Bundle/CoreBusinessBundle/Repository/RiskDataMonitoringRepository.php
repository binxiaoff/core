<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class RiskDataMonitoringRepository extends EntityRepository
{
    /**
     * @param array $status
     *
     * @return array
     */
    public function getMonitoringEvents()
    {
        $query = '
                SELECT
                  rdm.siren,
                  co.name,
                  p.id_project,
                  p.title,
                  p.status,
                  ps.label,
                  cr.type,
                  cr.value,
                  DATE(crh.added) AS added,
                  (SELECT cr_previous.value
                   FROM company_rating_history crh_previous
                     INNER JOIN company_rating cr_previous ON crh_previous.id_company_rating_history = cr_previous.id_company_rating_history
                   WHERE crh_previous.id_company = p.id_company 
                     AND cr.type = cr_previous.type 
                     AND crh_previous.id_company_rating_history < crh.id_company_rating_history
                   ORDER BY crh_previous.added DESC
                   LIMIT 1
                  ) AS previous_value
                FROM risk_data_monitoring rdm
                  INNER JOIN companies co ON rdm.siren = co.siren
                  INNER JOIN projects p ON co.id_company = p.id_company
                  INNER JOIN projects_status ps ON p.status = ps.status
                  INNER JOIN risk_data_monitoring_call_log rdmcl ON rdm.id = rdmcl.id_risk_data_monitoring
                  INNER JOIN company_rating cr ON rdmcl.id_company_rating_history = cr.id_company_rating_history
                  INNER JOIN company_rating_history crh ON rdmcl.id_company_rating_history = crh.id_company_rating_history
                WHERE rdm.end IS NULL
                GROUP BY p.status, p.id_company, p.id_project
                ORDER BY crh.added DESC, p.status DESC, p.id_project DESC';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }
}
