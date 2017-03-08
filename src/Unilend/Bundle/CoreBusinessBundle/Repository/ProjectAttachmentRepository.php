<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectAttachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

class ProjectAttachmentRepository extends EntityRepository
{
    /**
     * @param Projects|integer       $project
     * @param AttachmentType|integer $attachmentType
     *
     * @return ProjectAttachment
     */
    public function getAttachedAttachments($project, $attachmentType)
    {
        $qb = $this->createQueryBuilder('pa');
        $qb->innerJoin('UnilendCoreBusinessBundle:Attachment', 'a', Join::WITH, $qb->expr()->eq('pa.idAttachment', 'a.id'))
           ->where($qb->expr()->eq('a.idType', ':attachmentType'))
           ->andWhere($qb->expr()->eq('pa.idProject', ':project'))
           ->setParameter(':project', $project)
           ->setParameter(':attachmentType', $attachmentType);

        return $qb->getQuery()->getResult();
    }
}
