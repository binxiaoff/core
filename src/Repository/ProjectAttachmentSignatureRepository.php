<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\ProjectAttachmentSignature;

class ProjectAttachmentSignatureRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectAttachmentSignature::class);
    }

    /**
     * @param ProjectAttachmentSignature $projectAttachmentSignature
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectAttachmentSignature $projectAttachmentSignature)
    {
        $this->_em->persist($projectAttachmentSignature);
        $this->_em->flush();
    }
}
