<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\AttachmentSignature;

/**
 * @method AttachmentSignature|null find($id, $lockMode = null, $lockVersion = null)
 * @method AttachmentSignature|null findOneBy(array $criteria, array $orderBy = null)
 * @method AttachmentSignature[]    findAll()
 * @method AttachmentSignature[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AttachmentSignatureRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttachmentSignature::class);
    }

    /**
     * @param AttachmentSignature $attachmentSignature
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(AttachmentSignature $attachmentSignature)
    {
        $this->_em->persist($attachmentSignature);
        $this->_em->flush();
    }
}
