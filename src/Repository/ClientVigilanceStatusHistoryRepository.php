<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\ClientVigilanceStatusHistory;

class ClientVigilanceStatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientVigilanceStatusHistory::class);
    }

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
