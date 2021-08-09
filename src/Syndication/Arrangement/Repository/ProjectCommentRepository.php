<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Syndication\Arrangement\Entity\ProjectComment;

/**
 * @method ProjectComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectComment[]    findAll()
 * @method ProjectComment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectComment::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectComment $comment): void
    {
        $this->getEntityManager()->persist($comment);
        $this->getEntityManager()->flush();
    }
}
