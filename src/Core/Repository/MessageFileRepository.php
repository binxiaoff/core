<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Core\Entity\{File, Message, MessageFile, MessageStatus, Staff};

/**
 * @method MessageFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessageFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessageFile[]    findAll()
 * @method MessageFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageFileRepository extends ServiceEntityRepository
{
    /**
     * MessageFileRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageFile::class);
    }

    /**
     * @param MessageFile $messageFile
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(MessageFile $messageFile): void
    {
        $this->getEntityManager()->persist($messageFile);
        $this->getEntityManager()->flush();
    }

    /**
     * @param File  $file
     * @param Staff $recipient
     *
     * @return int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMessageFileByFileAndRecipient(File $file, Staff $recipient)
    {
        $queryBuilder = $this->createQueryBuilder('msgf');

        return $queryBuilder
            ->innerJoin(Message::class, 'msg', JOIN::WITH, 'msg.id = msgf.message')
            ->innerJoin(MessageStatus::class, 'msgst', JOIN::WITH, 'msgst.message = msg.id')
            ->where($queryBuilder->expr()->eq('msgf.file', ':file'))
            ->andWhere($queryBuilder->expr()->eq('msgst.recipient', ':recipient'))
            ->setParameters([
                'file'      => $file,
                'recipient' => $recipient,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

}
