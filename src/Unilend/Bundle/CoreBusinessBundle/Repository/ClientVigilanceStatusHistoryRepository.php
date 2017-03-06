<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ClientVigilanceStatusHistoryRepository extends EntityRepository
{

    public function getHistoryByAtypicalOperationStatus($detectionStatus)
    {
        $query = '
        SELECT cvsh.* FROM client_vigilance_status_history cvsh
        INNER JOIN client_atypical_operation cao ON cao.id_client = cvsh.id_client
        WHERE cvsh.id = (SELECT max(id) FROM client_vigilance_status_history cvsh2 WHERE cvsh2.id_client = cao.id_client)
          AND cao.detection_status = :detection_status 
        GROUP BY cvsh.id_client
        ';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['detection_status' => $detectionStatus])
            ->fetchAll(\PDO::FETCH_ASSOC);
    }
}
