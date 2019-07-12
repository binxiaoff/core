<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\AcceptationsLegalDocs;
use Unilend\Service\ServiceTerms\ServiceTermsManager;

/**
 * @method AcceptationsLegalDocs|null find($id, $lockMode = null, $lockVersion = null)
 * @method AcceptationsLegalDocs|null findOneBy(array $criteria, array $orderBy = null)
 * @method AcceptationsLegalDocs[]    findAll()
 * @method AcceptationsLegalDocs[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcceptationLegalDocsRepository extends ServiceEntityRepository
{
    /** @var ServiceTermsManager */
    private $serviceTermsManager;

    /**
     * @param ManagerRegistry     $registry
     * @param ServiceTermsManager $serviceTermsManager
     */
    public function __construct(ManagerRegistry $registry, ServiceTermsManager $serviceTermsManager)
    {
        parent::__construct($registry, AcceptationsLegalDocs::class);
        $this->serviceTermsManager = $serviceTermsManager;
    }

    /**
     * @param AcceptationsLegalDocs $acceptationsLegalDocs
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(AcceptationsLegalDocs $acceptationsLegalDocs): void
    {
        $this->getEntityManager()->persist($acceptationsLegalDocs);
        $this->getEntityManager()->flush();
    }

    /**
     * @param int $limit
     *
     * @return AcceptationsLegalDocs[]
     */
    public function findByIdLegalDocWithoutPfd(int $limit): array
    {
        $idTree = $this->serviceTermsManager->getCurrentVersionId();

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
