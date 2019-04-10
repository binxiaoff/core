<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{Attachment, AttachmentType, Transfer, TransferAttachment};

class TransferAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferAttachment::class);
    }

    /**
     * @param Transfer|integer       $transfer
     * @param AttachmentType|integer $attachmentType
     *
     * @return TransferAttachment[]
     */
    public function getAttachedAttachments($transfer, $attachmentType)
    {
        $qb = $this->createQueryBuilder('ta');
        $qb->innerJoin(Attachment::class, 'a', Join::WITH, $qb->expr()->eq('ta.idAttachment', 'a.id'))
           ->where($qb->expr()->eq('a.idType', ':attachmentType'))
           ->andWhere($qb->expr()->eq('ta.idTransfer', ':transfer'))
           ->setParameter(':transfer', $transfer)
           ->setParameter(':attachmentType', $attachmentType);

        return $qb->getQuery()->getResult();
    }
}
