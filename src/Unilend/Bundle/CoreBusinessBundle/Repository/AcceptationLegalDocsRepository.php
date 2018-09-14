<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\AcceptationsLegalDocs;

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
}
