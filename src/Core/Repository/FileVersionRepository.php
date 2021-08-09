<?php

declare(strict_types=1);

namespace KLS\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\FileVersion;

/**
 * @method FileVersion|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileVersion|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileVersion[]    findAll()
 * @method FileVersion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileVersion::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(FileVersion $fileVersion): void
    {
        $this->getEntityManager()->persist($fileVersion);
        $this->getEntityManager()->flush();
    }
}
