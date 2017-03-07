<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class ProjectAttachmentRepository extends EntityRepository
{
    /**
     * @param $project
     * @param $attachmentType
     *
     * @return array
     */
    public function getAttachedAttachments($project, $attachmentType)
    {
        var_dump($attachmentType->getId());
        $qb = $this->createQueryBuilder('pa');
        $qb->innerJoin('UnilendCoreBusinessBundle:Attachment', 'a', Join::WITH, $qb->expr()->eq('pa.idAttachment', 'a.id'))
           ->where($qb->expr()->eq('a.idType', ':attachmentType'))
           ->andWhere($qb->expr()->eq('pa.idProject', ':project'))
           ->setParameter(':project', $project)
           ->setParameter(':attachmentType', $attachmentType);

        return $qb->getQuery()->getResult();
    }
}
