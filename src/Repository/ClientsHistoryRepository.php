<?php

declare(strict_types=1);

namespace Unilend\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\DBALException;
use Unilend\Entity\{Clients, ClientsHistory};

/**
 * @method ClientsHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientsHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientsHistory[]    findAll()
 * @method ClientsHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientsHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientsHistory::class);
    }

    /**
     * @param Clients|int   $client
     * @param DateTime|null $lastLoginDate
     *
     * @throws DBALException
     *
     * @return array
     */
    public function getRecentLoginHistoryAndDevices($client, ?DateTime $lastLoginDate = null): array
    {
        if ($client instanceof Clients) {
            $client = $client->getIdClient();
        }
        if (null === $lastLoginDate) {
            $lastLoginDate = new DateTime('6 months ago');
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
            'lastLoginDate' => $lastLoginDate->format('Y-m-d H:i:s'),
        ];

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, $params)
            ->fetchAll()
        ;
    }
}
