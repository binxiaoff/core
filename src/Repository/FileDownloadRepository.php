<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Unilend\Core\Entity\FileDownload;

/**
 * @method FileDownload|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileDownload|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileDownload[]    findAll()
 * @method FileDownload[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileDownloadRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileDownload::class);
    }

    /**
     * @param FileDownload $fileDownload
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(FileDownload $fileDownload): void
    {
        $this->getEntityManager()->persist($fileDownload);
        $this->getEntityManager()->flush();
    }
}
