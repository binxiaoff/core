<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;

class LenderStatisticRepository extends EntityRepository
{

    /**
     * @param int $idWallet
     *
     * @return array
     */
    public function getValuesForIRR($idWallet)
    {
        $query = '
            SELECT
              o.added AS date,
              -ROUND(o.amount*100, 0) AS amount
            FROM operation o
              INNER JOIN operation_type ot ON o.id_type = ot.id
              INNER JOIN loans l ON o.id_loan = l.id_loan
            WHERE ot.label = "' . OperationType::LENDER_LOAN . '"
              AND l.id_lender = :idWallet

        UNION ALL

            SELECT
              e.date_echeance_reel AS date,
              CASE WHEN e.status_ra = 1 THEN e.capital_rembourse ELSE e.capital_rembourse + e.interets_rembourses END AS amount
            FROM
              echeanciers e
              INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE
              l.id_lender = :idWallet
              AND e.status = ' . Echeanciers::STATUS_REPAID . '

        UNION ALL

            SELECT
                e.date_echeance AS date,
                e.capital + e.interets AS amount
            FROM echeanciers e
              INNER JOIN projects p ON e.id_project = p.id_project
              INNER JOIN loans l ON e.id_loan = l.id_loan
            WHERE
                l.id_lender = :idWallet
                AND e.status = ' . Echeanciers::STATUS_PENDING . '
                AND p.status = ' . ProjectsStatus::REMBOURSEMENT . '

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
                            ps2.status = ' . ProjectsStatus::PROBLEME . '
                            AND psh2.id_project = e.id_project
                        ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                        LIMIT 1
                    )
                ) > 120 THEN "0" ELSE e.capital + e.interets END
                END AS amount
            FROM echeanciers e
              INNER JOIN projects p ON e.id_project = p.id_project
              INNER JOIN loans l ON e.id_loan = l.id_loan
              INNER JOIN companies com ON p.id_company = com.id_company
              INNER JOIN company_status cs2 ON cs2.id = com.id_status
            WHERE
                l.id_lender = :idWallet
                AND e.status = ' . Echeanciers::STATUS_PENDING . '
                AND p.status = ' . ProjectsStatus::PROBLEME . '
                AND cs2.label = \'' . CompanyStatus::STATUS_IN_BONIS . '\'

        UNION ALL

            SELECT
                e.date_echeance AS date,
                "0" AS amount
            FROM echeanciers e
              INNER JOIN projects p ON e.id_project = p.id_project
              INNER JOIN loans l ON e.id_loan = l.id_loan
              INNER JOIN companies com ON com.id_company = p.id_company
              INNER JOIN company_status cs ON cs.id = com.id_status
            WHERE
                l.id_lender = :idWallet
                AND e.status = ' . Echeanciers::STATUS_PENDING . '
                AND p.status >= ' . ProjectsStatus::REMBOURSEMENT . '
                AND cs.label IN (\'' . implode('\',\'', [
                CompanyStatus::STATUS_PRECAUTIONARY_PROCESS,
                CompanyStatus::STATUS_RECEIVERSHIP,
                CompanyStatus::STATUS_COMPULSORY_LIQUIDATION,
            ]) . '\')

        UNION ALL
        
            SELECT
              o_collection_capital.added AS date,
              ROUND(
                  (
                    SUM(o_collection_capital.amount) - IFNULL((
                    SELECT SUM(o_collection_capital_regul.amount)
                    FROM operation o_collection_capital_regul
                      INNER JOIN operation_sub_type ost ON o_collection_capital_regul.id_sub_type = ost.id
                    WHERE ost.label = \'' . OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION_REGULARIZATION . '\' AND o_collection_capital_regul.id_wallet_debtor = o_collection_capital.id_wallet_creditor AND DATE(o_collection_capital_regul.added) = DATE(o_collection_capital.added)
                  ),0) - IFNULL((
                    SELECT SUM(com.amount)
                    FROM operation com
                      INNER JOIN operation_type ot ON com.id_type = ot.id
                    WHERE ot.label = \'' . OperationType::COLLECTION_COMMISSION_LENDER . '\' AND com.id_wallet_debtor = o_collection_capital.id_wallet_creditor AND DATE(com.added) = DATE(o_collection_capital.added)
                  ),0) +  IFNULL((
                   SELECT SUM(com_regul.amount)
                   FROM operation com_regul
                     INNER JOIN operation_type ot ON com_regul.id_type = ot.id
                   WHERE ot.label = \'' . OperationType::COLLECTION_COMMISSION_LENDER_REGULARIZATION . '\' AND com_regul.id_wallet_creditor = o_collection_capital.id_wallet_creditor AND DATE(com_regul.added) = DATE(o_collection_capital.added)
                  ),0)
                  ) * 100
              ) AS amount
            FROM operation o_collection_capital
              INNER JOIN operation_sub_type ost ON o_collection_capital.id_sub_type = ost.id
            WHERE ost.label = \'' . OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION . '\'
              AND (
                o_collection_capital.id_wallet_creditor IN (
                  SELECT
                    DISTINCT (wallet.id)
                  FROM loans l
                    LEFT JOIN loan_transfer lt ON l.id_transfer = lt.id_loan_transfer
                    LEFT JOIN transfer ON lt.id_transfer = transfer.id_transfer
                    INNER JOIN wallet ON transfer.id_client_origin = wallet.id_client
                  WHERE l.id_lender = :idWallet
                )
                OR o_collection_capital.id_wallet_creditor = :idWallet
              )
            GROUP BY DATE(o_collection_capital.added);
        ';

        $values = $this->getEntityManager()->getConnection()->executeQuery($query, ['idWallet' => $idWallet])->fetchAll(\PDO::FETCH_ASSOC);

        return $values;
    }

}
