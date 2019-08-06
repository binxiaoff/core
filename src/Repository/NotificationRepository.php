<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use Unilend\Entity\{Clients, Notification};

/**
 * @method Notification|null find($id, $lockMode = null, $lockVersion = null)
 * @method Notification|null findOneBy(array $criteria, array $orderBy = null)
 * @method Notification[]    findAll()
 * @method Notification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NotificationRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @param Notification $notification
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Notification $notification): void
    {
        $this->getEntityManager()->persist($notification);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Notification $notification
     *
     * @throws ORMException
     */
    public function persist(Notification $notification): void
    {
        $this->getEntityManager()->persist($notification);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * @param Clients $client
     */
    public function markAllClientNotificationsAsRead(Clients $client): void
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->update(Notification::class, 'n')
            ->set('n.status', $queryBuilder->expr()->literal(Notification::STATUS_READ))
            ->set('n.updated', $queryBuilder->expr()->literal(date('Y-m-d H:i:s')))
            ->where('n.status = ' . Notification::STATUS_UNREAD)
            ->andWhere('n.client = :client')
            ->setParameter('client', $client)
        ;

        $queryBuilder->getQuery()->execute();
    }

    /**
     * @param Clients $client
     * @param array   $notifications
     */
    public function markAsRead(Clients $client, array $notifications): void
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->update(Notification::class, 'n')
            ->set('n.status', $queryBuilder->expr()->literal(Notification::STATUS_READ))
            ->set('n.updated', $queryBuilder->expr()->literal(date('Y-m-d H:i:s')))
            ->where('n.status = ' . Notification::STATUS_UNREAD)
            ->andWhere('n.id IN (:notifications)')
            ->andWhere('n.client = :client')
            ->setParameter('notifications', $notifications, Connection::PARAM_INT_ARRAY)
            ->setParameter('client', $client)
        ;

        $queryBuilder->getQuery()->execute();
    }

    /**
     * @param Clients $client
     * @param array   $type
     *
     * @throws NonUniqueResultException
     *
     * @return int
     */
    public function countClientUnreadNotifications(Clients $client, array $type = []): int
    {
        $queryBuilder = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.client = :client')
            ->andWhere('n.status = ' . Notification::STATUS_UNREAD)
            ->setParameter('client', $client)
        ;

        if (false === empty($type)) {
            $queryBuilder
                ->andWhere('n.type IN (:notificationType)')
                ->setParameter('notificationType', $type, Connection::PARAM_INT_ARRAY)
            ;
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
