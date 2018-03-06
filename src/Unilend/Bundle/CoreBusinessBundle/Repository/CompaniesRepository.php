<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Service\RiskDataMonitoring\MonitoringCycleManager;

class CompaniesRepository extends EntityRepository
{
    /**
     * @param int $maxDepositAmount
     *
     * @return array
     */
    public function getLegalEntitiesByCumulativeDepositAmount($maxDepositAmount)
    {
        $operationType = $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType');
        $queryBuilder  = $this->createQueryBuilder('c')
             ->select('IDENTITY(c.idClientOwner) AS idClient, c.capital, SUM(o.amount) AS depositAmount, GROUP_CONCAT(o.id) AS operation')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClientOwner = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.idWalletCreditor = w.id')
            ->where('o.idType = :operation_type')
            ->setParameter('operation_type', $operationType->findOneBy(['label' => OperationType::LENDER_PROVISION]))
            ->groupBy('o.idWalletCreditor')
            ->having('depositAmount >= c.capital')
            ->andHaving('depositAmount >= :max_deposit_amount')
            ->setParameter('max_deposit_amount', $maxDepositAmount);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string      $siren
     * @param string|null $provider
     * @param bool        $ongoing
     *
     * @return array
     */
    public function getMonitoredCompaniesBySiren(string $siren, ?string $provider = null, bool $ongoing = true): array
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:RiskDataMonitoring', 'rdm', Join::WITH, 'c.siren = rdm.siren')
            ->where('c.siren = :siren')
            ->setParameter('siren', $siren);

        if ($ongoing) {
            $queryBuilder->andWhere('rdm.end IS NULL');
        }

        if (null !== $provider) {
            $queryBuilder->andWhere('rdm.provider = :provider')
                ->setParameter('provider', $provider);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCountCompaniesInCollectiveProceedingBetweenDates(\DateTime $start, \DateTime $end)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $status = [
            CompanyStatus::STATUS_PRECAUTIONARY_PROCESS,
            CompanyStatus::STATUS_COMPULSORY_LIQUIDATION,
            CompanyStatus::STATUS_RECEIVERSHIP
        ];

        $query = 'SELECT COUNT(DISTINCT(csh.id_company))
                  FROM (SELECT MAX(id) AS max_id
                        FROM company_status_history csh_max
                        GROUP BY id_company) AS csh_max
                    INNER JOIN company_status_history csh ON csh_max.max_id = csh.id
                    INNER JOIN company_status cs ON csh.id_status = cs.id
                  WHERE
                    cs.label IN (:status)
                    AND csh.added BETWEEN :start AND :end';

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query, [
                'status' => $status,
                'start'  => $start->format('Y-m-d H:i:s'),
                'end'    => $end->format('Y-m-d H:i:s')
            ], [
                'status' => Connection::PARAM_STR_ARRAY,
                'start'  => \PDO::PARAM_STR,
                'end'    => \PDO::PARAM_STR
            ])->fetchAll();

        return $result;
    }

    /**
     * @param string $siren
     *
     * @return bool
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isProblematicCompany($siren)
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('COUNT(c.idCompany)')
            ->innerJoin('UnilendCoreBusinessBundle:CompanyStatusHistory', 'csh', Join::WITH,  'csh.idCompany = c.idCompany')
            ->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH,  'p.idCompany = c.idCompany')
            ->innerJoin('UnilendCoreBusinessBundle:ProjectsStatusHistory', 'psh', Join::WITH,  'psh.idProject = p.idProject')
            ->innerJoin('UnilendCoreBusinessBundle:CompanyStatus', 'cs', Join::WITH,  'cs.id = csh.idStatus')
            ->innerJoin('UnilendCoreBusinessBundle:ProjectsStatus', 'ps', Join::WITH,  'ps.idProjectStatus = psh.idProjectStatus')
            ->where('c.siren = :siren')
            ->setParameter('siren', $siren)
            ->andWhere('cs.label != :inBonis')
            ->setParameter('inBonis', CompanyStatus::STATUS_IN_BONIS)
            ->andWhere('ps.status IN (:projectStatus)')
            ->setParameter('projectStatus', [ProjectsStatus::PROBLEME, ProjectsStatus::LOSS]);

        return $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @param Companies $company
     *
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countDuplicatesByNameAndParent(Companies $company): int
    {
        return $this->createQueryBuilder('co')
            ->select('COUNT(co.idCompany)')
            ->where('LOWER(co.name) LIKE LOWER(:name)')
            ->andWhere('co.idParentCompany = :parent')
            ->setParameter('name', $company->getName())
            ->setParameter('parent', $company->getIdParentCompany())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array
     */
    public function getNotYetMonitoredSirenWithProjects(): array
    {
        $sirenExistSubQuery = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('rdm2')
            ->from('UnilendCoreBusinessBundle:RiskDataMonitoring', 'rdm2')
            ->where('rdm2.siren = co.siren')      ;

        $queryBuilder = $this->createQueryBuilder('co');
        $queryBuilder->select('DISTINCT co.siren')
            ->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH, 'p.idCompany = co.idCompany')
            ->where('p.status > :firstProjectStatus')
            ->andWhere('p.status NOT IN (:excludedStatus)')
            ->andWhere($queryBuilder->expr()->not($queryBuilder->expr()->exists($sirenExistSubQuery->getDQL())))
            ->setParameter('excludedStatus', MonitoringCycleManager::LONG_TERM_MONITORING_EXCLUDED_PROJECTS_STATUS)
            ->setParameter('firstProjectStatus', ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function getSirenWithProjectOrCompanyStatusNotToBeMonitored(): array
    {
        $queryBuilder = $this->createQueryBuilder('co');
        $queryBuilder->select('DISTINCT(co.siren) AS siren')
            ->innerJoin('UnilendCoreBusinessBundle:CompanyStatus', 'cs', Join::WITH, 'cs.id = co.idStatus')
            ->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH, 'co.idCompany = p.idCompany')
            ->innerJoin('UnilendCoreBusinessBundle:RiskDataMonitoring', 'rdm', Join::WITH, 'co.siren = rdm.siren')
            ->where('cs.label != :inBonis')
            ->orWhere('p.status IN (:finalStatus)')
            ->setParameter('inBonis', CompanyStatus::STATUS_IN_BONIS)
            ->setParameter('finalStatus', [ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE, ProjectsStatus::NOT_ELIGIBLE]);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getSirenWithActiveProjectsAndNoMonitoring()
    {
        $query = '
          SELECT
            DISTINCT(co.siren) AS siren 
          FROM companies co 
            INNER JOIN projects p ON co.id_company = p.id_company 
            INNER JOIN risk_data_monitoring rdm ON co.siren = rdm.siren 
          WHERE p.status > ' . ProjectsStatus::ABANDONED . ' 
            AND p.status NOT IN (' . implode(',', MonitoringCycleManager::LONG_TERM_MONITORING_EXCLUDED_PROJECTS_STATUS) . ') 
            AND rdm.end <= NOW() 
            AND (SELECT rdm2.end FROM risk_data_monitoring rdm2 WHERE rdm2.siren = co.siren ORDER BY rdm2.start DESC LIMIT 1) IS NOT NULL';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query)
            ->fetchAll();
    }
}