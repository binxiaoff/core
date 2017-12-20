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
     * @param Projects|int       $project
     * @param AttachmentType|int $attachmentType
     *
     * @return Attachment|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getProjectAttachmentByType($project, $attachmentType)
    {
        $queryBuilder = $this->createQueryBuilder('a');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:ProjectAttachment', 'pa', Join::WITH, 'a.id = pa.idAttachment')
           ->where('pa.idProject = :project')
           ->andWhere('a.idType = :attachmentType')
           ->setParameters(['project' => $project, 'attachmentType' => $attachmentType]);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Clients|int        $client
     * @param AttachmentType|int $attachmentType
     *
     * @return Attachment|null
     */
    public function findOneClientAttachmentByType($client, $attachmentType)
    {
        return $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:Attachment')->findOneBy([
            'idClient' => $client,
            'idType'   => $attachmentType,
            'archived' => null
        ]);
    }

    /**
     * @param Attachment $attachment
     *
     * @return Attachment|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findPreviousNotArchivedAttachment(Attachment $attachment)
    {
        $queryBuilder = $this->createQueryBuilder('a');
        $queryBuilder->where('a.idClient = :idClient')
            ->andWhere('a.idType = :idType')
            ->andWhere('a.archived IS NOT NULL')
            ->andWhere('a.id != :idAttachment')
            ->andWhere('a.added < :added')
            ->setParameter('idClient', $attachment->getClient())
            ->setParameter('idType', $attachment->getType())
            ->setParameter('idAttachment', $attachment->getId())
            ->setParameter('added', $attachment->getAdded())
            ->orderBy('a.added', 'DESC')
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
