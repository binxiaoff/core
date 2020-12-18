<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Core\Entity\{Message, Staff};

/**
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    /**
     * MessageRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @param Message $message
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Message $message): void
    {
        $this->getEntityManager()->persist($message);
        $this->getEntityManager()->flush();
    }

    /**
     * @param DateTimeImmutable $added
     * @param Staff             $sender
     * @param array             $messageThreads
     *
     * @return int|mixed|string
     */
    public function findBroadcastedMessagesByAddedSenderAndThreads(\DateTimeImmutable $added, Staff $sender, array $messageThreads)
    {
        $queryBuilder = $this->createQueryBuilder('msg');

        return $queryBuilder
            ->where($queryBuilder->expr()->eq('msg.added', ':added'))
            ->andWhere($queryBuilder->expr()->eq('msg.sender', ':sender'))
            ->andWhere($queryBuilder->expr()->in('msg.messageThread', ':messageThreads'))
            ->andWhere($queryBuilder->expr()->in('msg.broadcast', ':broadcast'))
            ->setParameters([
                'added'          => $added,
                'sender'         => $sender,
                'messageThreads' => $messageThreads,
                'broadcast'      => true,
            ])
            ->getQuery()
            ->getResult();
    }
}
