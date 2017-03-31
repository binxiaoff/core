<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
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
                o_withdraw.added AS date,
                ROUND((o_withdraw.amount + o_comission.amount) * 100) AS amount
            FROM operation o_withdraw
                INNER JOIN operation_type ot_withdraw ON o_withdraw.id_type = ot_withdraw.id AND ot_withdraw.label = ' . OperationType::BORROWER_WITHDRAW . '
                INNER JOIN operation o_comission ON o_withdraw.id_wallet_debtor = o_comission.id_wallet_debtor AND DATE(o_withdraw.added) = DATE(o_comission.added)
                INNER JOIN operation_type ot_comission ON o_comission.id_type = ot_comission.id AND  ot_comission.label = ' . OperationType::BORROWER_COMMISSION . '
            GROUP BY o_withdraw.id

        UNION ALL

            SELECT
                CASE WHEN ee.status_ra = 1 THEN ee.capital ELSE ee.capital + ee.interets END AS amount,
                (
                    SELECT CASE WHEN e.status = 1 THEN e.date_echeance_reel ELSE e.date_echeance END
                    FROM echeanciers e
                    WHERE
                        e.ordre = ee.ordre
                        AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            WHERE
                (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE
                        e2.ordre = ee.ordre
                        AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = 1

        UNION ALL

            SELECT
                ee.capital + ee.interets AS amount,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE
                        e.ordre = ee.ordre
                        AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            WHERE
                (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE
                        e2.ordre = ee.ordre
                        AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = 0
                AND p.status = ' . \projects_status::REMBOURSEMENT . '
                AND ee.id_project > 0

        UNION ALL

            SELECT
                CASE WHEN ee.date_echeance_emprunteur < NOW() THEN "0" ELSE ee.capital + ee.interets END AS montant,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE
                        e.ordre = ee.ordre
                        AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            WHERE
                (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE
                        e2.ordre = ee.ordre
                        AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = 0
                AND p.status IN (' . implode(',', [\projects_status::PROBLEME, \projects_status::PROBLEME_J_X]) . ')
                AND ee.id_project > 0

        UNION ALL

            SELECT
                CASE WHEN ee.date_echeance_emprunteur < NOW() THEN "0" ELSE CASE WHEN DATEDIFF (
                    NOW(),
                    (
                        SELECT psh2.added
                        FROM projects_status_history psh2
                        INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                        WHERE
                            ps2.status = ' . \projects_status::PROBLEME . '
                            AND psh2.id_project = ee.id_project
                        ORDER BY psh2.added DESC
                        LIMIT 1
                    )
                ) > 180 THEN "0" ELSE ee.capital + ee.interets END END AS amount,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE
                        e.ordre = ee.ordre
                        AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            WHERE
                (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE
                        e2.ordre = ee.ordre
                        AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = 0
                AND p.status = ' . \projects_status::RECOUVREMENT . '
                AND ee.id_project > 0

        UNION ALL

            SELECT
                0 AS amount,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE
                        e.ordre = ee.ordre
                        AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            WHERE
                (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE
                        e2.ordre = ee.ordre
                        AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = 0
                AND p.status IN (' . implode(',', [
                \projects_status::PROCEDURE_SAUVEGARDE,
                \projects_status::REDRESSEMENT_JUDICIAIRE,
                \projects_status::LIQUIDATION_JUDICIAIRE,
                \projects_status::DEFAUT
            ]) . ')
                AND ee.id_project > 0

        UNION ALL

            SELECT
              ROUND(o_recovery.amount * 100) AS amount,
              o_recovery.added               AS date
            FROM operation o_recovery
              INNER JOIN operation_type ot_recovery ON o_recovery.id_type = ot_recovery.id AND ot_recovery.label = ' . OperationType::BORROWER_PROVISION . '
              INNER JOIN operation o_comission ON o_recovery.id_wallet_creditor = o_comission.id_wallet_creditor AND o_recovery.id_project = o_comission.id_project AND DATE(o_recovery.added) = DATE(o_comission.added)
              INNER JOIN operation_type ot_comission ON o_comission.id_type = ot_comission.id AND ot_comission.label = ' . OperationType::COLLECTION_COMMISSION_PROVISION . '
            GROUP BY o_recovery.id';

        $values = $this->getEntityManager()->getConnection()->executeQuery($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $values;
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
                o_withdraw.added AS date,
                ROUND((o_withdraw.amount + o_comission.amount) * 100) AS amount
            FROM operation o_withdraw
                INNER JOIN operation_type ot_withdraw ON o_withdraw.id_type = ot_withdraw.id AND ot_withdraw.label = ' . OperationType::BORROWER_WITHDRAW . '
                INNER JOIN operation o_comission ON o_withdraw.id_wallet_debtor = o_comission.id_wallet_debtor AND DATE(o_withdraw.added) = DATE(o_comission.added)
                INNER JOIN operation_type ot_comission ON o_comission.id_type = ot_comission.id AND  ot_comission.label = ' . OperationType::BORROWER_COMMISSION . '
            AND (SELECT DATE(psh.added) FROM projects_status_history psh INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . \projects_status::REMBOURSEMENT . '
                  WHERE psh.id_project = o_withdraw.id_project
                  ORDER BY psh.id_project_status ASC LIMIT 1) BETWEEN :startDate AND :endDate
            GROUP BY o_withdraw.id

        UNION ALL

            SELECT
                CASE WHEN ee.status_ra = 1 THEN ee.capital ELSE ee.capital + ee.interets END AS montant,
                (
                    SELECT CASE WHEN e.status = 1 THEN e.date_echeance_reel ELSE e.date_echeance END
                    FROM echeanciers e
                    WHERE
                        e.ordre = ee.ordre
                        AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            WHERE
                (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE
                        e2.ordre = ee.ordre
                        AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = 1
                 AND (SELECT DATE(psh.added) FROM projects_status_history psh INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . \projects_status::REMBOURSEMENT . '
                  WHERE psh.id_project = ee.id_project
                  ORDER BY psh.id_project_status ASC LIMIT 1) BETWEEN :startDate AND :endDate

        UNION ALL

            SELECT
                ee.capital + ee.interets AS montant,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE
                        e.ordre = ee.ordre
                        AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            WHERE
                (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE
                        e2.ordre = ee.ordre
                        AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = 0
                AND p.status = ' . \projects_status::REMBOURSEMENT . '
                AND ee.id_project > 0
                AND (SELECT DATE(psh.added) FROM projects_status_history psh INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . \projects_status::REMBOURSEMENT . '
                  WHERE psh.id_project = ee.id_project
                  ORDER BY psh.id_project_status ASC LIMIT 1) BETWEEN :startDate AND :endDate

        UNION ALL

            SELECT
                CASE WHEN ee.date_echeance_emprunteur < NOW() THEN "0" ELSE ee.capital + ee.interets END AS montant,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE
                        e.ordre = ee.ordre
                        AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            WHERE
                (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE
                        e2.ordre = ee.ordre
                        AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = 0
                AND p.status IN (' . implode(',', [\projects_status::PROBLEME, \projects_status::PROBLEME_J_X]) . ')
                AND ee.id_project > 0
                AND (SELECT DATE(psh.added) FROM projects_status_history psh INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . \projects_status::REMBOURSEMENT . '
                  WHERE psh.id_project = ee.id_project
                  ORDER BY psh.id_project_status ASC LIMIT 1) BETWEEN :startDate AND :endDate

        UNION ALL

            SELECT
                CASE WHEN ee.date_echeance_emprunteur < NOW() THEN "0" ELSE CASE WHEN DATEDIFF (
                    NOW(),
                    (
                        SELECT psh2.added
                        FROM projects_status_history psh2
                        INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                        WHERE
                            ps2.status = ' . \projects_status::PROBLEME . '
                            AND psh2.id_project = ee.id_project
                        ORDER BY psh2.added DESC
                        LIMIT 1
                    )
                ) > 180 THEN "0" ELSE ee.capital + ee.interets END END AS montant,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE
                        e.ordre = ee.ordre
                        AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            WHERE
                (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE
                        e2.ordre = ee.ordre
                        AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = 0
                AND p.status = ' . \projects_status::RECOUVREMENT . '
                AND ee.id_project > 0
                AND (SELECT DATE(psh.added) FROM projects_status_history psh INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . \projects_status::REMBOURSEMENT . '
                  WHERE psh.id_project = ee.id_project
                  ORDER BY psh.id_project_status ASC LIMIT 1) BETWEEN :startDate AND :endDate

        UNION ALL

            SELECT
                0 AS montant,
                (
                    SELECT e.date_echeance
                    FROM echeanciers e
                    WHERE
                        e.ordre = ee.ordre
                        AND ee.id_project = e.id_project
                    LIMIT 1
                ) AS date
            FROM echeanciers_emprunteur ee
            INNER JOIN projects p ON ee.id_project = p.id_project
            WHERE
                (
                    SELECT e2.status
                    FROM echeanciers e2
                    WHERE
                        e2.ordre = ee.ordre
                        AND ee.id_project = e2.id_project
                    LIMIT 1
                ) = 0
                AND p.status IN (' . implode(',', [
                \projects_status::PROCEDURE_SAUVEGARDE,
                \projects_status::REDRESSEMENT_JUDICIAIRE,
                \projects_status::LIQUIDATION_JUDICIAIRE,
                \projects_status::DEFAUT
            ]) . ')
                AND ee.id_project > 0
                AND (SELECT DATE(psh.added) FROM projects_status_history psh INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . \projects_status::REMBOURSEMENT . '
                  WHERE psh.id_project = ee.id_project
                  ORDER BY psh.id_project_status ASC LIMIT 1) BETWEEN :startDate AND :endDate

        UNION ALL
        
            SELECT
                  ROUND(o_recovery.amount * 100) AS amount,
                  o_recovery.added               AS date
                FROM operation o_recovery
                  INNER JOIN operation_type ot_recovery ON o_recovery.id_type = ot_recovery.id AND ot_recovery.label = ' . OperationType::BORROWER_PROVISION . '
                  INNER JOIN operation o_comission ON o_recovery.id_wallet_creditor = o_comission.id_wallet_creditor AND o_recovery.id_project = o_comission.id_project AND DATE(o_recovery.added) = DATE(o_comission.added)
                  INNER JOIN operation_type ot_comission ON o_comission.id_type = ot_comission.id AND ot_comission.label = ' . OperationType::COLLECTION_COMMISSION_PROVISION . '
                  WHERE (SELECT DATE(psh.added) FROM projects_status_history psh INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status AND ps.status = ' . \projects_status::REMBOURSEMENT . '
                  WHERE psh.id_project = o_recovery.id_project
                  ORDER BY psh.id_project_status ASC LIMIT 1) BETWEEN :startDate AND :endDate 
                GROUP BY o_recovery.id';

        $values = $this->getEntityManager()->getConnection()->executeQuery($query, ['startDate' => $cohortStartDate, 'endDate' => $cohortEndDate])->fetchAll(\PDO::FETCH_ASSOC);

        return $values;
    }

    /**
     * @return null|UnilendStats
     */
    public function getLastUnilendIRR()
    {
        $qb = $this->createQueryBuilder('us');
        $qb->where('us.typeStat = "IRR"')
            ->orderBy('us.added', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
