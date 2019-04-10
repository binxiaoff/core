<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\{Clients, ClientsHistory};

class ClientsHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientsHistory::class);
    }

    /**
     * @param Clients|int    $client
     * @param \DateTime|null $lastLoginDate
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getRecentLoginHistoryAndDevices($client, ?\DateTime $lastLoginDate = null): array
    {
        if ($client instanceof Clients) {
            $client = $client->getIdClient();
        }
        if (null === $lastLoginDate) {
            $lastLoginDate = new \DateTime('6 months ago');
        }

        $query = '
            SELECT ordered_login_history.*
            FROM (SELECT ch.id_client, ch.city, p.fr, ch.added, ch.id_user_agent, ua.browser_name, ua.device_model, ua.device_type, ua.device_brand
              FROM clients_history ch
                 INNER JOIN user_agent ua ON ch.id_user_agent = ua.id
                 LEFT JOIN pays p ON p.iso = ch.country_iso_code
              WHERE ch.id_client = :idClient
              AND ch.added >= :lastLoginDate
              ORDER BY ch.added DESC) AS ordered_login_history
            GROUP BY ordered_login_history.id_user_agent
            ORDER BY ordered_login_history.added DESC
        ';

        $params = [
            'idClient'      => $client,
            'lastLoginDate' => $lastLoginDate->format('Y-m-d H:i:s')
        ];

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, $params)
            ->fetchAll();
    }
}
