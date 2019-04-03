<?php

namespace Unilend\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Unilend\Entity\Notifications;

class NotificationsRepository extends EntityRepository
{
    public function markAllLenderNotificationsAsRead($lenderId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(Notifications::class, 'n')
            ->set('n.status', $qb->expr()->literal(Notifications::STATUS_READ))
            ->where('n.status = ' . Notifications::STATUS_UNREAD)
            ->andWhere('n.idLender = :lenderId')
            ->setParameter('lenderId', $lenderId);

        $qb->getQuery()->execute();
    }

    /**
     * @param int   $lenderId
     * @param array $notifications
     */
    public function markLenderNotificationsAsRead($lenderId, array $notifications)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update(Notifications::class, 'n')
            ->set('n.status', $qb->expr()->literal(Notifications::STATUS_READ))
            ->where('n.status = ' . Notifications::STATUS_UNREAD)
            ->andWhere('n.idLender = :lenderId')
            ->andWhere('n.idNotification IN (:notifications)')
            ->setParameter('lenderId', $lenderId)
            ->setParameter('notifications', $notifications, Connection::PARAM_INT_ARRAY);

        $qb->getQuery()->execute();
    }

    /**
     * @param int      $lenderId
     * @param int|null $projectId
     * @param array    $notificationType
     *
     * @return int
     */
    public function countUnreadNotificationsForClient($lenderId, $projectId = null, $notificationType = [])
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.idNotification)')
            ->where('n.idLender = :lenderId')
            ->andWhere('n.status = ' . Notifications::STATUS_UNREAD)
            ->setParameter('lenderId', $lenderId);

        if (null !== $projectId) {
            $qb->andWhere('n.idProject = :projectId')
                ->setParameter('projectId', $projectId);
        }

        if (false === empty($notificationType)) {
            $qb->andWhere('n.type IN (:notificationType)')
                ->setParameter('notificationType', $notificationType, Connection::PARAM_INT_ARRAY);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
