<?php

declare(strict_types=1);

namespace KLS\Syndication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Syndication\Entity\ProjectFile;

/**
 * @method ProjectFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectFile[]    findAll()
 * @method ProjectFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectFile::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectFile $attachment): void
    {
        $this->getEntityManager()->persist($attachment);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(ProjectFile $attachment): void
    {
        $this->getEntityManager()->remove($attachment);
        $this->getEntityManager()->flush();
    }
}
