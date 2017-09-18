<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;

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
            ->select('c.idClientOwner AS idClient, c.capital, SUM(o.amount) AS depositAmount, GROUP_CONCAT(o.id) AS operation')
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
     * @param string|null $ratingType
     * @param bool        $ongoing
     *
     * @return array
     */
    public function getMonitoredCompaniesBySiren($siren, $ratingType = null, $ongoing = true)
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:RiskDataMonitoring', 'rdm', Join::WITH, 'c.siren = rdm.siren')
            ->where('c.siren = :siren')
            ->setParameter('siren', $siren);

        if ($ongoing) {
            $queryBuilder->andWhere('rdm.end IS NULL');
        }

        if (null !== $ratingType) {
            $queryBuilder->andWhere('rdm.ratingType = :ratingType')
                ->setParameter('ratingType', $ratingType);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function getCountCompaniesInCollectiveProceedingBetweenDates(\DateTime $start, \DateTime $end)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $status = [
            ProjectsStatus::PROCEDURE_SAUVEGARDE,
            ProjectsStatus::LIQUIDATION_JUDICIAIRE,
            ProjectsStatus::REDRESSEMENT_JUDICIAIRE
        ];

        $query = 'SELECT
                      COUNT(DISTINCT(id_company))
                    FROM (SELECT MAX(id_project_status_history) AS max_id_projects_status_history
                          FROM projects_status_history psh_max
                          GROUP BY id_project) AS psh_max
                      INNER JOIN projects_status_history psh ON psh_max.max_id_projects_status_history = psh.id_project_status_history
                      INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                      INNER JOIN projects p ON p.id_project = psh.id_project
                    WHERE
                      ps.status IN (:status)
                      AND psh.added BETWEEN :start AND :end';

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query, [
                'status' => $status,
                'start'  => $start->format('Y-m-d H:i:s'),
                'end'    => $end->format('Y-m-d H:i:s')
            ], [
                'status' => Connection::PARAM_INT_ARRAY,
                'start'  => \PDO::PARAM_STR,
                'end'    => \PDO::PARAM_STR
            ])->fetchAll();

        return $result;
    }
}
