<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;

class AttachmentRepository extends EntityRepository
{
    /**
     * @param $project
     * @param $attachmentType
     *
     * @return Attachment|null
     */
    public function getProjectAttachmentByType($project, $attachmentType)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->innerJoin('UnilendCoreBusinessBundle:ProjectAttachment', 'pa', Join::WITH, 'a.id = pa.idAttachment')
           ->where('pa.idProject = :project')
           ->andWhere('a.idType = :attachmentType')
           ->setParameters([':project' => $project, 'attachmentType' => $attachmentType]);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
