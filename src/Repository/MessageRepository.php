<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\Message;

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
     * @param array $messageThreads
     * @param       $body
     *
     * @return int|mixed|string
     */
    public function findMessagesByMessageThreadsAndBody(array $messageThreads, $body)
    {
        $queryBuilder = $this->createQueryBuilder(self::getAlias());

        return $queryBuilder
            ->where($queryBuilder->expr()->in(self::getAlias().'.messageThread', ':messageThreads'))
            ->andWhere($queryBuilder->expr()->eq(self::getAlias().'.body', ':body'))
            ->setParameters([
                'messageThreads' => $messageThreads,
                'body' => $body
            ])
            ->getQuery()
            ->getResult();
    }
}
