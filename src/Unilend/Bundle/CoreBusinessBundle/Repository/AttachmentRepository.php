<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

class AttachmentRepository extends EntityRepository
{
    /**
     * @param Projects|integer       $project
     * @param AttachmentType|integer $attachmentType
     *
     * @return Attachment|null
     */
    public function getProjectAttachmentByType($project, $attachmentType)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->innerJoin('UnilendCoreBusinessBundle:ProjectAttachment', 'pa', Join::WITH, 'a.id = pa.idAttachment')
           ->where('pa.idProject = :project')
           ->andWhere('a.idType = :attachmentType')
           ->setParameters(['project' => $project, 'attachmentType' => $attachmentType]);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Clients|integer        $client
     * @param AttachmentType|integer $attachmentType
     *
     * @return null|Attachment
     */
    public function findOneClientAttachmentByType($client, $attachmentType)
    {
        return $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneBy([
            'idClient' => $client,
            'idType'   => $attachmentType,
            'archived' => null
        ]);
    }
}
