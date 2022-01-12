<?php

declare(strict_types=1);

namespace KLS\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\MailLog;

/**
 * @method MailLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method MailLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method MailLog[]    findAll()
 * @method MailLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MailLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailLog::class);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function persist(MailLog $mailLog): void
    {
        $this->getEntityManager()->persist($mailLog);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
