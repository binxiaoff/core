<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Syndication\Entity\ProjectComment;

/**
 * @method ProjectComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectComment[]    findAll()
 * @method ProjectComment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectCommentRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectComment::class);
    }

    /**
     * @param ProjectComment $comment
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectComment $comment): void
    {
        $this->getEntityManager()->persist($comment);
        $this->getEntityManager()->flush();
    }
}
