<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Unilend\Entity\AttachmentDownload;

/**
 * @method AttachmentDownload|null find($id, $lockMode = null, $lockVersion = null)
 * @method AttachmentDownload|null findOneBy(array $criteria, array $orderBy = null)
 * @method AttachmentDownload[]    findAll()
 * @method AttachmentDownload[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AttachmentDownloadRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttachmentDownload::class);
    }

    /**
     * @param AttachmentDownload $attachmentDownload
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(AttachmentDownload $attachmentDownload): void
    {
        $this->getEntityManager()->persist($attachmentDownload);
        $this->getEntityManager()->flush();
    }
}
