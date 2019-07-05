<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\AcceptationsLegalDocs;
use Unilend\Service\TermsOfSaleManager;

class AcceptationLegalDocsRepository extends ServiceEntityRepository
{
    /** @var TermsOfSaleManager */
    private $termsOfSaleManager;

    /**
     * @param ManagerRegistry    $registry
     * @param TermsOfSaleManager $termsOfSaleManager
     */
    public function __construct(ManagerRegistry $registry, TermsOfSaleManager $termsOfSaleManager)
    {
        parent::__construct($registry, AcceptationsLegalDocs::class);
        $this->termsOfSaleManager = $termsOfSaleManager;
    }

    /**
     * @param int $limit
     *
     * @return AcceptationsLegalDocs[]
     */
    public function findByIdLegalDocWithoutPfd(int $limit): array
    {
        $idTree = $this->termsOfSaleManager->getCurrentVersionId();

        $queryBuilder = $this->createQueryBuilder('ald');
        $queryBuilder
            ->where('ald.idLegalDoc IN (:version)')
            ->andWhere('ald.pdfName IS NULL')
            ->orderBy('ald.idAcceptation', 'ASC')
            ->setParameter('version', $idTree)
            ->setMaxResults($limit)
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}
