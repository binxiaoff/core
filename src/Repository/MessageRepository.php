<?php

declare(strict_types=1);

namespace Unilend\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\Message;
use Unilend\Entity\Staff;

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
     * @return string
     */
    public static function getAlias(): string
    {
        return 'message';
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
        $queryBuilder = $this->createQueryBuilder(self::getAlias());

        return $queryBuilder
            ->where($queryBuilder->expr()->eq(self::getAlias() . '.added', ':added'))
            ->andWhere($queryBuilder->expr()->eq(self::getAlias() . '.sender', ':sender'))
            ->andWhere($queryBuilder->expr()->in(self::getAlias() . '.messageThread', ':messageThreads'))
            ->setParameters([
                'added'          => $added,
                'sender'         => $sender,
                'messageThreads' => $messageThreads,
                'broadcasted'    => true,
            ])
            ->getQuery()
            ->getResult();
    }
}
