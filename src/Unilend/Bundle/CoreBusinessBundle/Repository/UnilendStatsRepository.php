<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnilendStats;

class UnilendStatsRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function getDataForUnilendIRR()
    {
        $query = '
            SELECT
                - ROUND((o_withdraw.amount + o_commission.amount) * 100) AS amount,
                o_withdraw.added AS date
            FROM operation o_withdraw
                INNER JOIN operation_type ot_withdraw ON o_withdraw.id_type = ot_withdraw.id AND ot_withdraw.label = "' . OperationType::BORROWER_WITHDRAW . '"
                INNER JOIN operation o_commission ON o_withdraw.id_wallet_debtor = o_commission.id_wallet_debtor AND o_withdraw.id_project = o_commission.id_project 
                    AND o_commission.id_sub_type = (SELECT id FROM operation_sub_type WHERE label = "' . OperationSubType::BORROWER_COMMISSION_FUNDS . '")
            GROUP BY o_withdraw.id_project

        UNION ALL

            SELECT
                CASE WHEN ee.status_ra = ' . EcheanciersEmprunteur::STATUS_EARLY_REPAYMENT_DONE . ' THEN ee.capital ELSE ee.capital + ee.interets END AS amount,
                (
                    SELECT CASE WHEN e.status = ' . Echeanciers::STATUS_REPAID . ' THEN e.date_echeance_reel ELSE e.date_echeance END
                    FROM echeanciers e
                    WHERE e.ordre = ee.ordre AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            WHERE (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE e2.ordre = ee.ordre AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = ' . Echeanciers::STATUS_REPAID . '

        UNION ALL

            SELECT
                ee.capital + ee.interets AS amount,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE e.ordre = ee.ordre AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            WHERE (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE e2.ordre = ee.ordre AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = ' . Echeanciers::STATUS_PENDING . '
                AND p.status = ' . ProjectsStatus::REMBOURSEMENT . '
                AND ee.id_project > 0

        UNION ALL

            SELECT
                CASE WHEN ee.date_echeance_emprunteur < NOW() THEN "0" ELSE ee.capital + ee.interets END AS amount,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE e.ordre = ee.ordre AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            INNER JOIN companies c ON p.id_company = c.id_company
            INNER JOIN company_status cs ON cs.id = c.id_status
            WHERE (
                  SELECT e2.status
                  FROM echeanciers e2
                  WHERE e2.ordre = ee.ordre AND ee.id_project = e2.id_project
                  LIMIT 1
                ) = ' . Echeanciers::STATUS_PENDING . '
                AND p.status = ' . ProjectsStatus::PROBLEME . '
                AND (p.close_out_netting_date IS NULL OR p.close_out_netting_date = "0000-00-00")
                AND cs.label = :inBonis
                AND ee.id_project > 0
    
        UNION ALL

            SELECT
                CASE WHEN ee.date_echeance_emprunteur < NOW() THEN "0" ELSE CASE WHEN DATEDIFF (
                    NOW(),
                    (
                        SELECT psh2.added
                        FROM projects_status_history psh2
                        INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                        WHERE ps2.status = ' . ProjectsStatus::PROBLEME . ' AND psh2.id_project = ee.id_project
                        ORDER BY psh2.added DESC
                        LIMIT 1
                    )
                ) > ' . UnilendStats::DAYS_AFTER_LAST_PROBLEM_STATUS_FOR_STATISTIC_LOSS . ' THEN "0" ELSE ee.capital + ee.interets END END AS amount,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE e.ordre = ee.ordre AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            INNER JOIN companies com ON p.id_company = com.id_company
            INNER JOIN company_status cs ON cs.id = com.id_status
            WHERE (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE e2.ordre = ee.ordre AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = ' . Echeanciers::STATUS_PENDING . '
                AND p.status >= ' . ProjectsStatus::REMBOURSEMENT . '
                AND (p.close_out_netting_date IS NOT NULL AND p.close_out_netting_date != "0000-00-00")
                AND ee.id_project > 0
                AND cs.label = :inBonis

        UNION ALL

            SELECT
                0 AS amount,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE e.ordre = ee.ordre AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            INNER JOIN companies com ON com.id_company = p.id_company
            INNER JOIN company_status cs ON cs.id = com.id_status
            WHERE
                (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE e2.ordre = ee.ordre AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = ' . Echeanciers::STATUS_PENDING . '
                AND p.status >= ' . ProjectsStatus::REMBOURSEMENT . '
                AND cs.label IN (:companyStatusInProceeding)
                AND ee.id_project > 0

        UNION ALL

            SELECT
                ROUND(o_recovery.amount * 100) AS amount,
                o_recovery.added               AS date
            FROM operation o_recovery
            INNER JOIN operation_type ot_recovery ON o_recovery.id_type = ot_recovery.id AND ot_recovery.label = "' . OperationType::BORROWER_PROVISION . '"
            INNER JOIN operation o_commission ON o_recovery.id_wallet_creditor = o_commission.id_wallet_creditor AND o_recovery.id_project = o_commission.id_project AND DATE(o_recovery.added) = DATE(o_commission.added)
            INNER JOIN operation_type ot_commission ON o_commission.id_type = ot_commission.id AND ot_commission.label = "' . OperationType::COLLECTION_COMMISSION_PROVISION . '"
            GROUP BY o_recovery.id';

        $params = [
            'inBonis'                   => CompanyStatus::STATUS_IN_BONIS,
            'companyStatusInProceeding' => [
                CompanyStatus::STATUS_PRECAUTIONARY_PROCESS,
                CompanyStatus::STATUS_RECEIVERSHIP,
                CompanyStatus::STATUS_COMPULSORY_LIQUIDATION
            ]
        ];
        $types  = [
            'inBonis'                   => \PDO::PARAM_STR,
            'companyStatusInProceeding' => Connection::PARAM_STR_ARRAY
        ];

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, $params, $types)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $cohortStartDate
     * @param string $cohortEndDate
     *
     * @return array
     */
    public function getIRRValuesByCohort($cohortStartDate, $cohortEndDate)
    {
        $query = '
            SELECT
                -ROUND((o_withdraw.amount + o_commission.amount) * 100) AS amount,
                o_withdraw.added AS date
            FROM operation o_withdraw
            INNER JOIN operation_type ot_withdraw ON o_withdraw.id_type = ot_withdraw.id AND ot_withdraw.label = "' . OperationType::BORROWER_WITHDRAW . '"
            INNER JOIN operation o_commission ON o_withdraw.id_wallet_debtor = o_commission.id_wallet_debtor AND o_withdraw.id_project = o_commission.id_project
                AND o_commission.id_sub_type = (SELECT id FROM operation_sub_type WHERE label = "' . OperationSubType::BORROWER_COMMISSION_FUNDS . '")
                AND (
                    SELECT DATE(psh.added) 
                    FROM projects_status_history psh 
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                    WHERE psh.id_project = o_withdraw.id_project
                    ORDER BY psh.id_project_status ASC 
                    LIMIT 1
                ) BETWEEN :startDate AND :endDate
            GROUP BY o_withdraw.id

        UNION ALL

            SELECT
                CASE WHEN ee.status_ra = ' . EcheanciersEmprunteur::STATUS_EARLY_REPAYMENT_DONE . ' THEN ee.capital ELSE ee.capital + ee.interets END AS amount,
                (
                    SELECT CASE WHEN e.status = ' . Echeanciers::STATUS_REPAID . ' THEN e.date_echeance_reel ELSE e.date_echeance END
                    FROM echeanciers e
                    WHERE e.ordre = ee.ordre AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            WHERE (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE e2.ordre = ee.ordre AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = ' . Echeanciers::STATUS_REPAID . '
                AND (
                    SELECT DATE(psh.added) 
                    FROM projects_status_history psh 
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . ProjectsStatus::REMBOURSEMENT . ' 
                    WHERE psh.id_project = ee.id_project
                    ORDER BY psh.id_project_status ASC 
                    LIMIT 1
                ) BETWEEN :startDate AND :endDate

        UNION ALL

            SELECT
                ee.capital + ee.interets AS amount,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE e.ordre = ee.ordre AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            WHERE (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE e2.ordre = ee.ordre AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = ' . Echeanciers::STATUS_PENDING . '
                AND p.status = ' . ProjectsStatus::REMBOURSEMENT . '
                AND ee.id_project > 0
                AND (
                    SELECT DATE(psh.added) 
                    FROM projects_status_history psh 
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                    WHERE psh.id_project = ee.id_project
                    ORDER BY psh.id_project_status ASC
                    LIMIT 1
                ) BETWEEN :startDate AND :endDate

        UNION ALL

            SELECT
                CASE WHEN ee.date_echeance_emprunteur < NOW() THEN "0" ELSE ee.capital + ee.interets END AS amount,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE e.ordre = ee.ordre AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            INNER JOIN companies c ON c.id_company = p.id_company
            INNER JOIN company_status cs ON cs.id = c.id_status
            WHERE (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE e2.ordre = ee.ordre AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = ' . Echeanciers::STATUS_PENDING . '
                AND p.status = ' . ProjectsStatus::PROBLEME . '
                AND (p.close_out_netting_date IS NULL OR p.close_out_netting_date = "0000-00-00")
                AND cs.label = :inBonis
                AND ee.id_project > 0
                AND (
                    SELECT DATE(psh.added)
                    FROM projects_status_history psh 
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                    WHERE psh.id_project = ee.id_project
                    ORDER BY psh.id_project_status ASC 
                    LIMIT 1
                ) BETWEEN :startDate AND :endDate

        UNION ALL

            SELECT
                CASE WHEN ee.date_echeance_emprunteur < NOW() THEN "0" ELSE CASE WHEN DATEDIFF (
                    NOW(),
                    (
                        SELECT psh2.added
                        FROM projects_status_history psh2
                        INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                        WHERE ps2.status = ' . ProjectsStatus::PROBLEME . ' AND psh2.id_project = ee.id_project
                        ORDER BY psh2.added DESC
                        LIMIT 1
                    )
                ) > ' . UnilendStats::DAYS_AFTER_LAST_PROBLEM_STATUS_FOR_STATISTIC_LOSS . ' THEN "0" ELSE ee.capital + ee.interets END END AS amount,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE e.ordre = ee.ordre AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            INNER JOIN companies c ON c.id_company = p.id_company
            INNER JOIN company_status cs ON cs.id = c.id_status
            WHERE (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE e2.ordre = ee.ordre AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = ' . Echeanciers::STATUS_PENDING . '
                AND p.status = ' . ProjectsStatus::PROBLEME . '
                AND (p.close_out_netting_date IS NOT NULL AND p.close_out_netting_date != "0000-00-00")
                AND cs.label = :inBonis
                AND ee.id_project > 0
                AND (
                    SELECT DATE(psh.added) 
                    FROM projects_status_history psh 
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                    WHERE psh.id_project = ee.id_project
                    ORDER BY psh.id_project_status ASC 
                    LIMIT 1
                ) BETWEEN :startDate AND :endDate

        UNION ALL

            SELECT
                0 AS amount,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE e.ordre = ee.ordre AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            INNER JOIN companies c ON c.id_company = p.id_company
            INNER JOIN company_status cs ON cs.id = c.id_status
            WHERE (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE e2.ordre = ee.ordre AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = ' . Echeanciers::STATUS_PENDING . '
                AND p.status >= ' . ProjectsStatus::REMBOURSEMENT . ' 
                AND cs.label IN (:companyStatusInProceeding)
                AND ee.id_project > 0
                AND (
                    SELECT DATE(psh.added) 
                    FROM projects_status_history psh 
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                    WHERE psh.id_project = ee.id_project
                    ORDER BY psh.id_project_status ASC 
                    LIMIT 1
                ) BETWEEN :startDate AND :endDate

        UNION ALL
        
            SELECT
                ROUND(o_recovery.amount * 100) AS amount,
                o_recovery.added               AS date
            FROM operation o_recovery
            INNER JOIN operation_type ot_recovery ON o_recovery.id_type = ot_recovery.id AND ot_recovery.label = "' . OperationType::BORROWER_PROVISION . '"
            INNER JOIN operation o_commission ON o_recovery.id_wallet_creditor = o_commission.id_wallet_creditor AND o_recovery.id_project = o_commission.id_project AND DATE(o_recovery.added) = DATE(o_commission.added)
            INNER JOIN operation_type ot_commission ON o_commission.id_type = ot_commission.id AND ot_commission.label = "' . OperationType::COLLECTION_COMMISSION_PROVISION . '"
            WHERE (
                    SELECT DATE(psh.added) 
                    FROM projects_status_history psh 
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                    WHERE psh.id_project = o_recovery.id_project
                    ORDER BY psh.id_project_status ASC 
                    LIMIT 1
                ) BETWEEN :startDate AND :endDate 
            GROUP BY o_recovery.id';

        $params = [
            'startDate'                 => $cohortStartDate,
            'endDate'                   => $cohortEndDate,
            'inBonis'                   => CompanyStatus::STATUS_IN_BONIS,
            'companyStatusInProceeding' => [
                CompanyStatus::STATUS_PRECAUTIONARY_PROCESS,
                CompanyStatus::STATUS_RECEIVERSHIP,
                CompanyStatus::STATUS_COMPULSORY_LIQUIDATION
            ]
        ];
        $types = [
            'startDate'                 => \PDO::PARAM_STR,
            'endDate'                   => \PDO::PARAM_STR,
            'inBonis'                   => \PDO::PARAM_STR,
            'companyStatusInProceeding' => Connection::PARAM_STR_ARRAY
        ];

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, $params, $types)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param \DateTime $date
     * @param $typeStat
     *
     * @return null|UnilendStats
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findStatisticAtDate(\DateTime $date, string $typeStat): ?UnilendStats
    {
        $qb = $this->createQueryBuilder('us');
        $qb->where('DATE(us.added) = :date')
            ->andWhere('us.typeStat = :typeStat')
            ->orderBy('us.added', 'DESC')
            ->setMaxResults(1)
            ->setParameter('typeStat', $typeStat)
            ->setParameter('date', $date->format('y-m-d'));

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $cohortStartDate
     * @param string $cohortEndDate
     *
     * @return array
     */
    public function getOptimisticIRRValuesByCohort($cohortStartDate, $cohortEndDate)
    {
        $query = '
            SELECT
              -ROUND((o_withdraw.amount + o_commission.amount) * 100) AS amount,
              o_withdraw.added                                       AS date
            FROM operation o_withdraw
              INNER JOIN operation_type ot_withdraw ON o_withdraw.id_type = ot_withdraw.id AND ot_withdraw.label = "' . OperationType::BORROWER_WITHDRAW . '"
              INNER JOIN operation o_commission ON o_withdraw.id_wallet_debtor = o_commission.id_wallet_debtor AND o_withdraw.id_project = o_commission.id_project
                AND o_commission.id_sub_type = (SELECT id FROM operation_sub_type WHERE label = "' . OperationSubType::BORROWER_COMMISSION_FUNDS . '")
                AND (
                     SELECT psh.added
                     FROM projects_status_history psh
                       INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                     WHERE psh.id_project = o_withdraw.id_project
                     ORDER BY psh.id_project_status ASC
                     LIMIT 1
                     ) BETWEEN :startDate AND :endDate
                    GROUP BY o_withdraw.id

            UNION ALL

            SELECT
                CASE WHEN ee.status_ra = ' . EcheanciersEmprunteur::STATUS_EARLY_REPAYMENT_DONE . ' 
                THEN ee.capital 
                ELSE ee.capital + ee.interets 
                END AS amount,
                (
                    SELECT CASE WHEN e.status = ' . Echeanciers::STATUS_REPAID . ' 
                    THEN e.date_echeance_reel 
                    ELSE e.date_echeance END
                    FROM echeanciers e
                    WHERE
                        e.ordre = ee.ordre
                        AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            WHERE (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE
                        e2.ordre = ee.ordre
                        AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = ' . Echeanciers::STATUS_REPAID . '
                 AND (
                        SELECT DATE(psh.added) 
                        FROM projects_status_history psh 
                            INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                        WHERE psh.id_project = ee.id_project
                        ORDER BY psh.id_project_status ASC LIMIT 1
                      ) BETWEEN :startDate AND :endDate

            UNION ALL

            SELECT
              ee.capital + ee.interets AS amount,
              (
               SELECT CASE 
                 WHEN e.status = ' . Echeanciers::STATUS_REPAID . '
                 THEN e.date_echeance_reel 
                 ELSE e.date_echeance 
                 END
               FROM echeanciers e
               WHERE e.ordre = ee.ordre AND ee.id_project = e.id_project
              LIMIT 1
              ) AS date
            FROM echeanciers_emprunteur ee
            WHERE (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE
                        e2.ordre = ee.ordre
                        AND ee.id_project = e2.id_project
                    LIMIT 1
                   ) = ' . Echeanciers::STATUS_PENDING . '
            AND (
                 SELECT psh.added
                 FROM projects_status_history psh 
                   INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = 80
                 WHERE psh.id_project = ee.id_project
                 ORDER BY psh.id_project_status ASC
                 LIMIT 1
                 ) BETWEEN :startDate AND :endDate';

        $values = $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['startDate' => $cohortStartDate, 'endDate' => $cohortEndDate])
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $values;
    }

    /**
     * @param \DateTime $dateLimit
     *
     * @return array
     */
    public function getOptimisticIRRValuesUntilDateLimit(\DateTime $dateLimit)
    {
        $query = '
            SELECT
                -ROUND((o_withdraw.amount + o_commission.amount) * 100) AS amount,
                o_withdraw.added                                       AS date
            FROM operation o_withdraw
            INNER JOIN operation_type ot_withdraw ON o_withdraw.id_type = ot_withdraw.id AND ot_withdraw.label = "' . OperationType::BORROWER_WITHDRAW . '"
            INNER JOIN operation o_commission ON o_withdraw.id_wallet_debtor = o_commission.id_wallet_debtor AND o_withdraw.id_project = o_commission.id_project
                AND o_commission.id_sub_type = (SELECT id FROM operation_sub_type WHERE label = "' . OperationSubType::BORROWER_COMMISSION_FUNDS . '")
            WHERE (
                    SELECT added
                    FROM projects_status_history psh
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                        AND psh.id_project = o_withdraw.id_project
                    ORDER BY added ASC
                    LIMIT 1
                ) <= :end
            GROUP BY o_withdraw.id

            UNION ALL

            SELECT
                CASE WHEN ee.status_ra = ' . EcheanciersEmprunteur::STATUS_EARLY_REPAYMENT_DONE . ' 
                THEN ee.capital 
                ELSE ee.capital + ee.interets 
                END AS amount,
                (
                    SELECT CASE WHEN e.status = ' . Echeanciers::STATUS_REPAID . ' 
                    THEN e.date_echeance_reel 
                    ELSE e.date_echeance END
                    FROM echeanciers e
                    WHERE
                        e.ordre = ee.ordre
                        AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            WHERE (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE e2.ordre = ee.ordre AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = ' . Echeanciers::STATUS_REPAID . '
                AND (
                    SELECT added
                    FROM projects_status_history psh
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . ' AND psh.id_project = ee.id_project
                    ORDER BY added ASC
                    LIMIT 1
                ) <= :end

            UNION ALL

            SELECT
                ee.capital + ee.interets AS amount,
                (
                    SELECT CASE 
                    WHEN e.status = ' . Echeanciers::STATUS_REPAID . '
                    THEN e.date_echeance_reel
                    ELSE e.date_echeance
                    END
                    FROM echeanciers e
                    WHERE e.ordre = ee.ordre AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            WHERE (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE e2.ordre = ee.ordre AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = ' . Echeanciers::STATUS_PENDING . '
                AND (
                    SELECT added
                    FROM projects_status_history psh
                    INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . ' AND psh.id_project = ee.id_project
                    ORDER BY added ASC
                    LIMIT 1
                ) <= :end';

        $values = $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['end' => $dateLimit->format('Y-m-d H:i:s')])
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $values;
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function getAvailableDatesForStatisticType($type)
    {
        $queryBuilder = $this->createQueryBuilder('us');
        $queryBuilder->select('DATE(us.added) AS availableDate','us.added')
            ->where('us.typeStat = :type')
            ->groupBy('availableDate')
            ->orderBy('us.added', 'DESC')
            ->setParameter('type', $type);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \DateTime $end
     * @param int       $months
     *
     * @return array
     */
    public function getTrimesterIncidenceRate(\DateTime $end, int $months): array
    {
        $queryBuilder = $this->createQueryBuilder('us');
        $queryBuilder
            ->where('us.typeStat = :trimesterType')
            ->andWhere('TIMESTAMPDIFF(MONTH, us.added, :end) <= :months')
            ->orderBy('us.added', 'ASC')
            ->setParameter('trimesterType', UnilendStats::TYPE_TRIMESTER_INCIDENCE_RATE)
            ->setParameter('months', $months)
            ->setParameter('end', $end);

        return $queryBuilder->getQuery()->getResult();
    }
}
