<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\ProjectComment;

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
    public function save(ProjectComment $comment)
    {
        $this->_em->persist($comment);
        $this->_em->flush($comment);
    }
}
