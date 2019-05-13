<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{Attachment, AttachmentSignature, AttachmentType, Project, ProjectAttachment, ProjectAttachmentType, ProjectAttachmentTypeCategory};

/**
 * @method ProjectAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectAttachment[]    findAll()
 * @method ProjectAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectAttachmentRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectAttachment::class);
    }

    /**
     * @param Project|int        $project
     * @param AttachmentType|int $attachmentType
     *
     * @return ProjectAttachment[]
     */
    public function getAttachedAttachmentsByType($project, $attachmentType): array
    {
        $queryBuilder = $this->createQueryBuilder('pa');
        $queryBuilder
            ->innerJoin('pa.attachment', 'a')
            ->where($queryBuilder->expr()->eq('a.type', ':attachmentType'))
            ->andWhere($queryBuilder->expr()->eq('pa.project', ':project'))
            ->setParameter(':project', $project)
            ->setParameter(':attachmentType', $attachmentType)
            ->addOrderBy('a.added', 'DESC')
            ->addOrderBy('a.id', 'DESC')
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Project|int $project
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
            ->innerJoin(ProjectAttachment::class, 'pa', Join::WITH, $queryBuilder->expr()->eq('pa.attachment', 'a.id'))
            ->innerJoin(ProjectAttachmentType::class, 'pat', Join::WITH, $queryBuilder->expr()->eq('pat.idType', 'a.idType'))
            ->innerJoin(ProjectAttachmentTypeCategory::class, 'cat', Join::WITH, $queryBuilder->expr()->eq('cat.id', 'pat.idCategory'))
            ->where($queryBuilder->expr()->eq('pa.project', ':project'))
            ->orderBy('cat.rank', 'ASC')
            ->addOrderBy('pat.rank', 'ASC')
            ->addOrderBy('a.added', 'ASC')
            ->setParameter(':project', $project)
        ;

        $attachments = $queryBuilder->getQuery()->getResult();

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select('-1 AS categoryId, \'Legacy\' AS categoryName, at.label AS typeName, a.id AS attachmentId, a.path, a.originalName, at.downloadable')
            ->leftJoin(Attachment::class, 'a')
            ->innerJoin(AttachmentType::class, 'at', Join::WITH, $queryBuilder->expr()->eq('a.idType', 'at.id'))
            ->innerJoin(ProjectAttachment::class, 'pa', Join::WITH, $queryBuilder->expr()->eq('pa.attachment', 'a.id'))
            ->leftJoin(ProjectAttachmentType::class, 'pat', Join::WITH, $queryBuilder->expr()->eq('pat.idType', 'a.idType'))
            ->where($queryBuilder->expr()->eq('pa.project', ':project'))
            ->andWhere($queryBuilder->expr()->isNull('pat.id'))
            ->orderBy('at.label', 'ASC')
            ->addOrderBy('a.added', 'ASC')
            ->setParameter(':project', $project)
        ;

        $legacy = $queryBuilder->getQuery()->getResult();

        return array_merge($attachments, $legacy);
    }

    /**
     * @param Project    $project
     * @param array|null $orderBy
     *
     * @return array
     */
    public function getAttachmentsWithoutSignature(Project $project, ?array $orderBy = null): array
    {
        $queryBuilder = $this->createQueryBuilder('pa');
        $queryBuilder
            ->leftJoin(AttachmentSignature::class, 's', Join::WITH, 'pa.attachment = s.attachment')
            ->where('pa.project = :project')
            ->andWhere($queryBuilder->expr()->isNull('s.id'))
            ->setParameter('project', $project)
        ;

        if (null === $orderBy) {
            $queryBuilder->orderBy('pa.added', 'ASC');
        }

        if ($orderBy) {
            foreach ($orderBy as $field => $direction) {
                $queryBuilder->addOrderBy('pa.' . $field, $direction);
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Project $project
     *
     * @return array
     */
    public function getAttachmentsWithSignature(Project $project): array
    {
        $queryBuilder = $this->createQueryBuilder('pa');
        $queryBuilder
            ->innerJoin(AttachmentSignature::class, 's', Join::WITH, 'pa.attachment = s.attachment')
            ->where('pa.project = :project')
            ->setParameter('project', $project)
            ->orderBy('pa.added', 'ASC')
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}
