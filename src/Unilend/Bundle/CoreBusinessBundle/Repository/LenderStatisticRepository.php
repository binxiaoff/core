<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\LenderStatistic;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class LenderStatisticRepository extends EntityRepository
{

    /**
     * @param string $idWallet
     * @param string $idLender
     *
     * @return array
     */
    public function getValuesForIRR($idWallet, $idLender)
    {
        $query = '
            SELECT
              added AS date,
              -ROUND(amount*100, 0) AS amount
            FROM operation o
            INNER JOIN operation_type ot ON o.id_type = ot.id
            WHERE ot.label = "' . OperationType::LENDER_LOAN . '"
                  AND id_wallet_debtor = :idWallet

        UNION ALL

            SELECT
                e.date_echeance AS date,
                e.capital + e.interets AS amount
            FROM echeanciers e
              INNER JOIN projects p ON e.id_project = p.id_project
              INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE
                l.id_lender = :idLender
                AND e.status = ' . \echeanciers::STATUS_PENDING . '
                AND p.status = ' . \projects_status::REMBOURSEMENT . '

        UNION ALL

            SELECT
                e.date_echeance AS date,
                CASE WHEN e.date_echeance < NOW() THEN "0" ELSE e.capital + e.interets END AS amount
            FROM echeanciers e
              INNER JOIN projects p ON e.id_project = p.id_project
              INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE
                l.id_lender = :idLender
                AND e.status = ' . \echeanciers::STATUS_PENDING . '
                AND p.status IN (' . implode(',', [\projects_status::PROBLEME, \projects_status::PROBLEME_J_X]) . ')

        UNION ALL

            SELECT
                e.date_echeance AS date,
                CASE WHEN e.date_echeance < NOW() THEN "0" ELSE
                CASE WHEN DATEDIFF(NOW(),
                    (
                        SELECT psh2.added
                        FROM projects_status_history psh2
                        INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                        WHERE
                            ps2.status = ' . \projects_status::PROBLEME . '
                            AND psh2.id_project = e.id_project
                        ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                        LIMIT 1
                    )
                ) > 180 THEN "0" ELSE e.capital + e.interets END
                END AS amount
            FROM echeanciers e
              INNER JOIN projects p ON e.id_project = p.id_project
              INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE
                l.id_lender = :idLender
                AND e.status = ' . \echeanciers::STATUS_PENDING . '
                AND p.status = ' . \projects_status::RECOUVREMENT . '

        UNION ALL

            SELECT
                e.date_echeance AS date,
                "0" AS amount
            FROM echeanciers e
              INNER JOIN projects p ON e.id_project = p.id_project
              INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE
                l.id_lender = :idLender
                AND e.status = ' . \echeanciers::STATUS_PENDING . '
                AND p.status IN (' . implode(',', [
                \projects_status::PROCEDURE_SAUVEGARDE,
                \projects_status::REDRESSEMENT_JUDICIAIRE,
                \projects_status::LIQUIDATION_JUDICIAIRE,
                \projects_status::DEFAUT
            ]) . ')

        UNION ALL
        
        (
            SELECT
                o_capital.added AS date,
                ROUND(
                    IF(
                        o_interest.amount IS NOT NULL,
                        (o_capital.amount + o_interest.amount),
                        IF(o_recovery.id IS NOT NULL,
                           (o_capital.amount - o_recovery.amount),
                           o_capital.amount))*100) AS amount
            FROM operation o_capital
            INNER JOIN operation_type ot_capital ON o_capital.id_type = ot_capital.id
              LEFT JOIN operation o_interest ON o_capital.id_repayment_schedule = o_interest.id_repayment_schedule 
              INNER JOIN operation_type ot_interest ON o_interest.id_type = ot_interest.id AND ot_interest.label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '"
              LEFT JOIN operation o_recovery ON o_capital.id_project = o_recovery.id_project AND o_capital.id_wallet_creditor = o_recovery.id_wallet_debtor 
              INNER JOIN operation_type ot_recovery ON o_recovery.id_type = ot_recovery.id AND ot_recovery.label = "' . OperationType::COLLECTION_COMMISSION_LENDER . '"
            WHERE
              o_capital.id_type = "' . OperationType::CAPITAL_REPAYMENT . '"
              AND (
                o_capital.id_wallet_creditor IN (SELECT
                                                 DISTINCT(transfer.id_client_origin)
                                               FROM loans l
                                                 LEFT JOIN loan_transfer lt ON l.id_transfer = lt.id_loan_transfer
                                                 LEFT JOIN transfer ON lt.id_transfer = transfer.id_transfer
                                               WHERE l.id_lender = :idLender)
                OR o_capital.id_wallet_creditor = :idWallet)
            GROUP BY IF(o_capital.id_repayment_schedule IS NOT NULL, o_capital.id_repayment_schedule, o_capital.id)
        )';

        $values = $this->getEntityManager()->getConnection()->executeQuery($query, [
                'idLender' => $idLender,
                'idWallet' => $idWallet
            ])->fetchAll(\PDO::FETCH_ASSOC);

        return $values;
    }

    /**
     * @param Wallet $wallet
     *
     * @return null|LenderStatistic
     */
    public function getLastIRRForLender(Wallet $wallet)
    {
        $qb = $this->createQueryBuilder('ls');
        $qb->where('ls.idWallet = :idWallet')
            ->andWhere('ls.typeStat = ":typeStat"' )
            ->orderBy('ls.added', 'DESC')
            ->setMaxResults(1)
            ->setParameter('idWallet', $wallet)
            ->setParameter('typeStat', LenderStatistic::TYPE_STAT_IRR);

        return $qb->getQuery()->getOneOrNullResult();
    }

}
