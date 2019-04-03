<?php

namespace Unilend\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{Attachment, AttachmentType, ProjectAttachment, ProjectAttachmentType, ProjectAttachmentTypeCategory, Projects};

class ProjectAttachmentRepository extends EntityRepository
{
    /**
     * @param Projects|int       $project
     * @param AttachmentType|int $attachmentType
     *
     * @return ProjectAttachment[]
     */
    public function getAttachedAttachmentsByType($project, $attachmentType): array
    {
        $queryBuilder = $this->createQueryBuilder('pa');
        $queryBuilder
            ->innerJoin(Attachment::class, 'a', Join::WITH, $queryBuilder->expr()->eq('pa.idAttachment', 'a.id'))
            ->where($queryBuilder->expr()->eq('a.idType', ':attachmentType'))
            ->andWhere($queryBuilder->expr()->eq('pa.idProject', ':project'))
            ->setParameter(':project', $project)
            ->setParameter(':attachmentType', $attachmentType)
            ->addOrderBy('a.added', 'DESC')
            ->addOrderBy('a.id', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Projects|int $project
     *
     * @return array
     */
    public function getAttachedAttachmentsWithCategories($project): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select('cat.id AS categoryId, cat.name AS categoryName, pat.name AS typeName, a.id AS attachmentId, a.path, a.originalName, at.downloadable')
            ->leftJoin(Attachment::class, 'a')
            ->innerJoin(AttachmentType::class, 'at', Join::WITH, $queryBuilder->expr()->eq('a.idType', 'at.id'))
            ->innerJoin(ProjectAttachment::class, 'pa', Join::WITH, $queryBuilder->expr()->eq('pa.idAttachment', 'a.id'))
            ->innerJoin(ProjectAttachmentType::class, 'pat', Join::WITH, $queryBuilder->expr()->eq('pat.idType', 'a.idType'))
            ->innerJoin(ProjectAttachmentTypeCategory::class, 'cat', Join::WITH, $queryBuilder->expr()->eq('cat.id', 'pat.idCategory'))
            ->where($queryBuilder->expr()->eq('pa.idProject', ':project'))
            ->orderBy('cat.rank', 'ASC')
            ->addOrderBy('pat.rank', 'ASC')
            ->addOrderBy('a.added', 'ASC')
            ->setParameter(':project', $project);

        $attachments = $queryBuilder->getQuery()->getResult();

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select('-1 AS categoryId, \'Legacy\' AS categoryName, at.label AS typeName, a.id AS attachmentId, a.path, a.originalName, at.downloadable')
            ->leftJoin(Attachment::class, 'a')
            ->innerJoin(AttachmentType::class, 'at', Join::WITH, $queryBuilder->expr()->eq('a.idType', 'at.id'))
            ->innerJoin(ProjectAttachment::class, 'pa', Join::WITH, $queryBuilder->expr()->eq('pa.idAttachment', 'a.id'))
            ->leftJoin(ProjectAttachmentType::class, 'pat', Join::WITH, $queryBuilder->expr()->eq('pat.idType', 'a.idType'))
            ->where($queryBuilder->expr()->eq('pa.idProject', ':project'))
            ->andWhere($queryBuilder->expr()->isNull('pat.id'))
            ->orderBy('at.label', 'ASC')
            ->addOrderBy('a.added', 'ASC')
            ->setParameter(':project', $project);

        $legacy = $queryBuilder->getQuery()->getResult();

        return array_merge($attachments, $legacy);
    }
}
