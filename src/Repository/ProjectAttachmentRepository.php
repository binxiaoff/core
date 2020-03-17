<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\ProjectAttachment;

/**
 * @method ProjectAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectAttachment[]    findAll()
 * @method ProjectAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectAttachmentRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectAttachment::class);
    }

    /**
     * @param ProjectAttachment $attachment
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectAttachment $attachment): void
    {
        $this->getEntityManager()->persist($attachment);
        $this->getEntityManager()->flush();
    }
}
