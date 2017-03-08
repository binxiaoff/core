<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Transfer;
use Unilend\Bundle\CoreBusinessBundle\Entity\TransferAttachment;

class TransferAttachmentRepository extends EntityRepository
{
    /**
     * @param Transfer|integer       $transfer
     * @param AttachmentType|integer $attachmentType
     *
     * @return TransferAttachment[]
     */
    public function getAttachedAttachments($transfer, $attachmentType)
    {
        $qb = $this->createQueryBuilder('ta');
        $qb->innerJoin('UnilendCoreBusinessBundle:Attachment', 'a', Join::WITH, $qb->expr()->eq('ta.idAttachment', 'a.id'))
           ->where($qb->expr()->eq('a.idType', ':attachmentType'))
           ->andWhere($qb->expr()->eq('ta.idTransfer', ':transfer'))
           ->setParameter(':transfer', $transfer)
           ->setParameter(':attachmentType', $attachmentType);

        return $qb->getQuery()->getResult();
    }
}
