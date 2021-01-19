<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Core\Entity\MessageFile;

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
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageFile::class);
    }

    /**
     * @param MessageFile $messageFile
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(MessageFile $messageFile): void
    {
        $this->getEntityManager()->persist($messageFile);
        $this->getEntityManager()->flush();
    }

    /**
     * @param MessageFile $messageStatus
     *
     * @throws ORMException
     */
    public function persist(MessageFile $messageStatus): void
    {
        $this->getEntityManager()->persist($messageStatus);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
