<?php

declare(strict_types=1);

namespace Unilend\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Syndication\Entity\ProjectFile;

/**
 * @method ProjectFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectFile[]    findAll()
 * @method ProjectFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectFileRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectFile::class);
    }

    /**
     * @param ProjectFile $attachment
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectFile $attachment): void
    {
        $this->getEntityManager()->persist($attachment);
        $this->getEntityManager()->flush();
    }

    /**
     * @param ProjectFile $attachment
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(ProjectFile $attachment): void
    {
        $this->getEntityManager()->remove($attachment);
        $this->getEntityManager()->flush();
    }
}
