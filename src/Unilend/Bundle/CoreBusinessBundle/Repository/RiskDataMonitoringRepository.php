<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class RiskDataMonitoringRepository extends EntityRepository
{

    public function getMonitoringEventsByProjectStatus(array $status)
    {
        $query = '
                SELECT
                  rdm.siren,
                  co.name,
                  p.id_project,
                  p.title,
                  ps.label,
                  cr.type,
                  cr.value,
                  crh.added,
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
                WHERE p.status IN (:status)
                GROUP BY p.status, p.id_company, p.id_project
                ORDER BY crh.added DESC, p.status ASC';

        return $this->getEntityManager()->getConnection()->executeQuery($query, ['status' => $status], ['status' => Connection::PARAM_INT_ARRAY])->fetchAll(\PDO::FETCH_ASSOC);
    }

}
