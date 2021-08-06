<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Unilend\Core\Entity\{MailQueue};

/**
 * @method MailQueue|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailQueue|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailQueue[]    findAll()
 * @method MailQueue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailQueueRepository extends ServiceEntityRepository
{
    /**
     * MailQueueRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailQueue::class);
    }

    /**
     * @return EntityManager|EntityManagerInterface
     */
    public function getEntityManager()
    {
        return parent::getEntityManager();
    }

    /**
     * @throws Exception
     *
     * @return MailQueue[]
     */
    public function getPendingMails(?int $limit = null): array
    {
        return $this->createQueryBuilder('mq')
            ->where('mq.status = :status')
            ->andWhere('mq.scheduledAt <= :now')
            ->setParameters([
                'status' => MailQueue::STATUS_PENDING,
                'now'    => new DateTime(),
            ])
            ->setMaxResults(\is_numeric($limit) ? $limit : null)
            ->orderBy('mq.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
