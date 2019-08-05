<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use Unilend\Entity\{Attachment, AttachmentType, Clients, Project, ProjectAttachment};

/**
 * @method Attachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Attachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Attachment[]    findAll()
 * @method Attachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AttachmentRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attachment::class);
    }

    /**
     * @param Attachment $attachment
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Attachment $attachment)
    {
        $this->getEntityManager()->persist($attachment);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Project|int        $project
     * @param AttachmentType|int $attachmentType
     *
     * @throws NonUniqueResultException
     *
     * @return Attachment|null
     */
    public function getProjectAttachmentByType($project, $attachmentType)
    {
        $queryBuilder = $this->createQueryBuilder('a');
        $queryBuilder->innerJoin(ProjectAttachment::class, 'pa', Join::WITH, 'a.id = pa.idAttachment')
            ->where('pa.idProject = :project')
            ->andWhere('a.idType = :attachmentType')
            ->setParameters(['project' => $project, 'attachmentType' => $attachmentType])
        ;

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
        return $this->getEntityManager()->getRepository(Attachment::class)->findOneBy([
            'idClient' => $client,
            'idType'   => $attachmentType,
            'archived' => null,
        ]);
    }

    /**
     * @param Attachment $attachment
     *
     * @throws NonUniqueResultException
     *
     * @return Attachment|null
     */
    public function findPreviousNotArchivedAttachment(Attachment $attachment)
    {
        $queryBuilder = $this->createQueryBuilder('a');
        $queryBuilder->where('a.idClient = :idClient')
            ->andWhere('a.idType = :idType')
            ->andWhere('a.archived IS NOT NULL')
            ->andWhere('a.id != :idAttachment')
            ->andWhere('a.added < :added')
            ->setParameter('idClient', $attachment->getClientOwner())
            ->setParameter('idType', $attachment->getType())
            ->setParameter('idAttachment', $attachment->getId())
            ->setParameter('added', $attachment->getAdded())
            ->orderBy('a.added', 'DESC')
            ->setMaxResults(1)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
