<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{AcceptationsLegalDocs, Wallet, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\TermsOfSaleManager;

class AcceptationLegalDocsRepository extends EntityRepository
{
    /**
     * @param array $legalDocs
     * @param int   $limit
     *
     * @return AcceptationsLegalDocs[]
     */
    public function findByIdLegalDocWithoutPfd(array $legalDocs, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('ald');
        $queryBuilder
            ->where('ald.idLegalDoc IN (:version)')
            ->andWhere('ald.pdfName IS NULL')
            ->orderBy('ald.idAcceptation', 'ASC')
            ->setParameter('version', $legalDocs)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Can be deleted after all previous accepted terms of sale have been generated (BLD-320)
     *
     * @param int $limit
     *
     * @return AcceptationsLegalDocs[]
     */
    public function findWithoutPfdForLender(int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('ald');
        $queryBuilder
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'w.idClient = ald.idClient')
            ->innerJoin(WalletType::class, 'wt', Join::WITH, 'wt.id = w.idType')
            ->andWhere('ald.pdfName IS NULL')
            ->andWhere('wt.label = :walletLabel')
            ->andWhere('ald.idLegalDoc != :rootFolderIdTree')
            ->orderBy('ald.idAcceptation', 'ASC')
            ->setParameter('walletLabel', WalletType::LENDER)
            ->setParameter('rootFolderIdTree', TermsOfSaleManager::ID_TREE_ROOT_SECTION_LENDER_TOS)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }
}
