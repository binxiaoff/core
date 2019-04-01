<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Entity\{Companies, CompanyStatus, CompanyStatusHistory};

class CompanyStatusHistoryRepository extends EntityRepository
{
    /**
     * @param Companies|int $company
     *
     * @return array
     */
    public function getNotificationContent($company)
    {
        if ($company instanceof Companies) {
            $company = $company->getIdCompany();
        }

        $queryBuilder = $this->createQueryBuilder('csh');
        $queryBuilder->select('cs.label, csh.siteContent, csh.added')
            ->innerJoin(CompanyStatus::class, 'cs', Join::WITH, 'cs.id = csh.idStatus')
            ->where('csh.idCompany = :companyId')
            ->andWhere('cs.label != :inBonis')
            ->setParameters(['companyId' => $company, 'inBonis' => CompanyStatus::STATUS_IN_BONIS])
            ->orderBy('csh.added', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int|Companies   $company
     * @param array $companyStatusLabel
     *
     * @return null|CompanyStatusHistory
     */
    public function findFirstHistoryByCompanyAndStatus($company, array $companyStatusLabel)
    {
        $queryBuilder = $this->createQueryBuilder('csh')
            ->innerJoin(CompanyStatus::class, 'cs', Join::WITH, 'cs.id = csh.idStatus')
            ->where('csh.idCompany = :companyId')
            ->setParameter('companyId', $company)
            ->andWhere('cs.label IN (:companyStatusLabel)')
            ->setParameter('companyStatusLabel', $companyStatusLabel, Connection::PARAM_STR_ARRAY)
            ->orderBy('csh.added', 'ASC')
            ->addOrderBy('csh.id', 'ASC')
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param \DateTime $dateAdded
     * @param array     $companyStatusLabel
     *
     * @return CompanyStatusHistory[]
     */
    public function getCompanyStatusChangesOnDate(\DateTime $dateAdded, array $companyStatusLabel)
    {
        $queryBuilder = $this->createQueryBuilder('csh')
            ->innerJoin(CompanyStatus::class, 'cs', Join::WITH, 'cs.id = csh.idStatus')
            ->andWhere('DATE(csh.added) = :date')
            ->andWhere('cs.label IN (:status)')
            ->setParameter(':date', $dateAdded->format('Y-m-d'))
            ->setParameter(':status', $companyStatusLabel, Connection::PARAM_STR_ARRAY);

        return $queryBuilder->getQuery()->getResult();
    }
}
