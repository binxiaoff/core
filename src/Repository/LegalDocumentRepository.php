<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\LegalDocument;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

/**
 * @method LegalDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method LegalDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method LegalDocument[]    findAll()
 * @method LegalDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LegalDocumentRepository extends ServiceEntityRepository
{
    /** @var ServiceTermsManager */
    private $serviceTermsManager;

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
