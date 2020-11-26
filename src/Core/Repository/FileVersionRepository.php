<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Core\Entity\FileVersion;

/**
 * @method FileVersion|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileVersion|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileVersion[]    findAll()
 * @method FileVersion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileVersionRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileVersion::class);
    }

    /**
     * @param FileVersion $fileVersion
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(FileVersion $fileVersion): void
    {
        $this->getEntityManager()->persist($fileVersion);
        $this->getEntityManager()->flush();
    }
}
