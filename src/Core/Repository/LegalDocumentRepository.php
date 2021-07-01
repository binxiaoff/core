<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalDocument::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(LegalDocument $legalDocument): void
    {
        $this->getEntityManager()->persist($legalDocument);
        $this->getEntityManager()->flush();
    }
}
