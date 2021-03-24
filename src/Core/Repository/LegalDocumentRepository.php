<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\LegalDocument;

/**
 * @method LegalDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method LegalDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method LegalDocument[]    findAll()
 * @method LegalDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LegalDocumentRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalDocument::class);
    }

    /**
     * @param LegalDocument $legalDocument
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(LegalDocument $legalDocument): void
    {
        $this->getEntityManager()->persist($legalDocument);
        $this->getEntityManager()->flush();
    }
}
