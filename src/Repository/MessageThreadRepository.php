<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Unilend\Entity\{MessageThread, Project};

/**
 * @method MessageThread|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessageThread|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessageThread[]    findAll()
 * @method MessageThread[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageThreadRepository extends ServiceEntityRepository
{
    /**
     * MessageThreadRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageThread::class);
    }

    /**
     * @param MessageThread $messageThread
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(MessageThread $messageThread)
    {
        $this->getEntityManager()->persist($messageThread);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Project $project
     *
     * @return int|mixed|string
     */
    public function findMessageThreadsByProject(Project $project)
    {
        $queryBuilder = $this->createQueryBuilder('ms');

        return $queryBuilder
            ->innerJoin('ms.projectParticipation', 'pp')
            ->where($queryBuilder->expr()->eq('pp.project', ':project'))
            ->setParameters(['project' => $project])
            ->getQuery()
            ->getResult();
    }
}
