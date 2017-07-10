<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\Operation;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\PaysV2;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Doctrine\DBAL\Connection;

class OperationRepository extends EntityRepository
{
    public function getOperationByTypeAndAmount($typeLabel, $amount)
    {
        $criteria = [
            'idType' => $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => $typeLabel]),
            'amount' => $amount
        ];
        $operator = [
            'idType' => Comparison::EQ,
            'amount' => Comparison::GTE
        ];

        return $this->getOperationBy($criteria, $operator);
    }

    /**
     * @param Wallet    $wallet
     * @param double    $amount
     * @param \DateTime $added
     *
     * @return Operation[]
     */
    public function getWithdrawOperationByWallet(Wallet $wallet, $amount, \DateTime $added)
    {
        $criteria = [
            'idType'         => $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_WITHDRAW]),
            'idWalletDebtor' => $wallet,
            'amount'         => $amount,
            'added'          => $added
        ];
        $operator = [
            'idType'         => Comparison::EQ,
            'idWalletDebtor' => Comparison::EQ,
            'amount'         => Comparison::GTE,
            'added'          => Comparison::GTE
        ];

        return $this->getOperationBy($criteria, $operator);
    }

    /**
     * @param Wallet    $wallet
     * @param \DateTime $date
     *
     * @return Operation[]
     */
    public function getWithdrawAndProvisionOperationByDateAndWallet(Wallet $wallet, \DateTime $date)
    {
        $qb = $this->createQueryBuilder('o')
            ->where('o.idType IN (:walletType)')
            ->setParameter('walletType', [
                $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_WITHDRAW])->getId(),
                $this->getEntityManager()->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_PROVISION])->getId(),
            ])
            ->andWhere('o.idWalletCreditor = :idWallet OR o.idWalletDebtor = :idWallet')
            ->setParameter('idWallet', $wallet)
            ->andWhere('o.added >= :added')
            ->setParameter('added', $date);

        return $qb->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
    }

    /**
     * @param array $criteria [field => value]
     * @param array $operator [field => operator]
     *
     * @return Operation[]
     */
    private function getOperationBy(array $criteria = [], array $operator = [])
    {
        $qb = $this->createQueryBuilder('op');
        $qb->select('op');

        foreach ($criteria as $field => $value) {
            $qb->andWhere('op.' . $field . $operator[$field] . ':' . $field)
                ->setParameter($field, $value);
        }
        $qb->orderBy('op.added', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int       $idClient
     * @param \DateTime $end
     *
     * @return bool|string
     */
    public function getRemainingDueCapitalAtDate($idClient, \DateTime $end)
    {
        $end->setTime(23, 59, 59);

        $query = '
            SELECT
              SUM(o_loan.amount) - SUM(o_repayment.amount)
            FROM wallet_balance_history wbh
              INNER JOIN wallet w ON wbh.id_wallet = w.id
              LEFT JOIN operation o_loan ON wbh.id_operation = o_loan.id AND o_loan.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::LENDER_LOAN . '")
              LEFT JOIN operation o_repayment ON wbh.id_operation = o_repayment.id AND o_repayment.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::CAPITAL_REPAYMENT . '")
            WHERE wbh.added <= :end
              AND wbh.id_project IN (SELECT DISTINCT (p.id_project) FROM projects p
                                       INNER JOIN projects_status_history psh ON p.id_project = psh.id_project
                                       INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                                     WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                                       AND psh.added <= :end
                                       AND p.status != ' . ProjectsStatus::DEFAUT . ')
              AND w.id_client = :idClient';

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, ['end' => $end->format('Y-m-d H:i:s'), 'idClient' => $idClient]);

        return $statement->fetchColumn();
    }

    /**
     * @param Wallet $creditorWallet
     * @param array  $operationTypes
     * @param array  $operationSubTypes
     * @param int    $year
     *
     * @return mixed
     */
    public function sumCreditOperationsByTypeAndYear(Wallet $creditorWallet, $operationTypes, $operationSubTypes = null, $year = null)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(o.amount)')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:operationTypes)')
            ->andWhere('o.idWalletCreditor = :idWallet')
            ->setParameter('operationTypes', $operationTypes, Connection::PARAM_STR_ARRAY)
            ->setParameter('idWallet', $creditorWallet->getId());

        if (null !== $operationSubTypes) {
            $qb->innerJoin('UnilendCoreBusinessBundle:OperationSubType', 'ost', Join::WITH, 'o.idSubType = ost.id')
                ->andWhere('ost.label IN (:operationSubTypes)')
                ->setParameter('operationSubTypes', $operationSubTypes, Connection::PARAM_STR_ARRAY);
        }

        if (null !== $year) {
            $qb->andWhere('o.added BETWEEN :start AND :end')
                ->setParameter('start', $year . '-01-01- 00:00:00')
                ->setParameter('end', $year . '-12-31 23:59:59');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /***
     * @param Wallet $debtorWallet
     * @param array  $operationTypes
     * @param array  $operationSubTypes
     * @param int    $year
     *
     * @return mixed
     */
    public function sumDebitOperationsByTypeAndYear(Wallet $debtorWallet, $operationTypes, $operationSubTypes = null, $year = null)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(o.amount)')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:operationTypes)')
            ->andWhere('o.idWalletDebtor = :idWallet')
            ->setParameter('operationTypes', $operationTypes, Connection::PARAM_STR_ARRAY)
            ->setParameter('idWallet', $debtorWallet->getId());

        if (null !== $operationSubTypes) {
            $qb->innerJoin('UnilendCoreBusinessBundle:OperationSubType', 'ost', Join::WITH, 'o.idSubType = ost.id')
                ->andWhere('ost.label IN (:operationSubTypes)')
                ->setParameter('operationSubTypes', $operationSubTypes, Connection::PARAM_STR_ARRAY);
        }

        if (null !== $year) {
            $qb->andWhere('o.added BETWEEN :start AND :end')
                ->setParameter('start', $year . '-01-01- 00:00:00')
                ->setParameter('end', $year . '-12-31 23:59:59');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet $wallet
     * @param int    $year
     *
     * @return bool|string
     */
    public function sumCapitalRepaymentsInEeaExceptFrance(Wallet $wallet, $year)
    {
        $query = 'SELECT
                     SUM(IF(tlih.id_pays IN (:eeaCountries),o_capital.amount, 0)) AS capital
                  FROM operation o_capital
                     LEFT JOIN (SELECT
                                  o.id_wallet_creditor,
                                  o.id,
                                  (SELECT lih.id_pays
                                   FROM lenders_imposition_history lih
                                   WHERE id_lender = o.id_wallet_creditor AND DATE(lih.added) <= DATE(o.added)
                                   ORDER BY lih.added DESC
                                   LIMIT 1) AS id_pays
                                FROM operation o
                                  INNER JOIN operation_type ot ON o.id_type = ot.id
                                WHERE ot.label = "' . OperationType::CAPITAL_REPAYMENT . '" AND o.id_wallet_creditor = :idWallet) AS tlih ON o_capital.id = tlih.id
                    WHERE o_capital.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::CAPITAL_REPAYMENT . '")
                    AND LEFT(o_capital.added, 4) = :year
                    AND o_capital.id_wallet_creditor = :idWallet';

        $bind  = [
            'year'         => $year,
            'idWallet'     => $wallet->getId(),
            'eeaCountries' => PaysV2::EUROPEAN_ECONOMIC_AREA
        ];
        $types = [
            'year'         => \PDO::PARAM_INT,
            'idWallet'     => \PDO::PARAM_INT,
            'eeaCountries' => Connection::PARAM_INT_ARRAY
        ];

        return $this->getEntityManager()->getConnection()->executeQuery($query, $bind, $types)->fetchColumn();
    }

    /**
     * @param Wallet $wallet
     * @param int    $year
     *
     * @return bool|string
     */
    public function sumNetInterestRepaymentsNotInEeaExceptFrance(Wallet $wallet, $year)
    {
        $query = 'SELECT
                      SUM(IF(tlih.id_pays IN (:eeaCountries), o_interest.amount, 0)) AS interest
                    FROM operation o_interest
                     LEFT JOIN (SELECT 
                                  SUM(amount) AS amount,
                                  id_repayment_schedule
                                 FROM operation
                                  WHERE operation.id_wallet_debtor = :idWallet
                                  AND operation.id_type IN (SELECT id FROM operation_type WHERE label IN (:taxOperations))
                                  GROUP BY id_repayment_schedule) AS o_taxes ON o_interest.id_repayment_schedule = o_taxes.id_repayment_schedule
                     LEFT JOIN (SELECT
                                  o.id_wallet_creditor,
                                  o.id,
                                  (SELECT lih.id_pays
                                   FROM lenders_imposition_history lih
                                   WHERE id_lender = o.id_wallet_creditor AND DATE(lih.added) <= DATE(o.added)
                                   ORDER BY lih.added DESC
                                   LIMIT 1) AS id_pays
                                FROM operation o
                                  INNER JOIN operation_type ot ON o.id_type = ot.id
                                WHERE ot.label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '" AND o.id_wallet_creditor = :idWallet) AS tlih ON o_interest.id = tlih.id
                    WHERE o_interest.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '")
                    AND LEFT(o_interest.added, 4) = :year
                         AND o_interest.id_wallet_creditor = :idWallet';

        $bind  = [
            'year'          => $year,
            'idWallet'      => $wallet->getId(),
            'taxOperations' => OperationType::TAX_TYPES_FR,
            'eeaCountries'  => PaysV2::EUROPEAN_ECONOMIC_AREA
        ];
        $types = [
            'year'          => \PDO::PARAM_INT,
            'idWallet'      => \PDO::PARAM_INT,
            'taxOperations' => Connection::PARAM_STR_ARRAY,
            'eeaCountries'  => Connection::PARAM_INT_ARRAY
        ];

        return $this->getEntityManager()->getConnection()->executeQuery($query, $bind, $types)->fetchColumn();
    }

    /**
     * @param Wallet $wallet
     * @param int    $year
     *
     * @return bool|string
     */
    public function getGrossInterestPaymentsInFrance(Wallet $wallet, $year)
    {
        $query = 'SELECT
                      SUM(IF(tlih.id_pays = 1 OR tlih.id_pays IS NULL OR tlih.id_pays = "", o_interest.amount, 0)) AS sum66
                    FROM operation o_interest
                      LEFT JOIN (SELECT
                                   o.id_wallet_creditor,
                                   o.id,
                                   (SELECT lih.id_pays
                                    FROM lenders_imposition_history lih
                                      WHERE id_lender = o.id_wallet_creditor AND added <= o.added
                                    ORDER BY added DESC
                                    LIMIT 1) AS id_pays
                                 FROM operation o
                                   INNER JOIN operation_type ot ON o.id_type = ot.id
                                 WHERE ot.label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '" AND o.id_wallet_creditor = :idWallet) AS tlih ON o_interest.id = tlih.id
                    WHERE o_interest.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '")
                      AND LEFT(o_interest.added, 4) = :year 
                      AND o_interest.id_wallet_creditor =  :idWallet';

        return $this->getEntityManager()->getConnection()->executeQuery($query, ['year' => $year, 'idWallet' => $wallet->getId()])->fetchColumn();
    }

    /**
     * @param Echeanciers|int $idRepaymentSchedule
     *
     * @return null|float
     */
    public function getTaxAmountByRepaymentScheduleId($idRepaymentSchedule)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(o.amount)')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:taxTypes)')
            ->andWhere('o.idRepaymentSchedule = :idRepaymentSchedule')
            ->setParameter('taxTypes', OperationType::TAX_TYPES_FR, Connection::PARAM_STR_ARRAY)
            ->setParameter('idRepaymentSchedule', $idRepaymentSchedule)
            ->setCacheable(true);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Echeanciers|int $idRepaymentSchedule
     *
     * @return null|float
     */
    public function getGrossAmountByRepaymentScheduleId($idRepaymentSchedule)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(o.amount)')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:repaymentTypes)')
            ->andWhere('o.idRepaymentSchedule = :idRepaymentSchedule')
            ->setParameter('repaymentTypes', [OperationType::CAPITAL_REPAYMENT, OperationType::GROSS_INTEREST_REPAYMENT], Connection::PARAM_STR_ARRAY)
            ->setParameter('idRepaymentSchedule', $idRepaymentSchedule)
            ->setCacheable(true);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Echeanciers|int $idRepaymentSchedule
     *
     * @return null|float
     */
    public function getNetAmountByRepaymentScheduleId($idRepaymentSchedule)
    {
        return bcsub($this->getGrossAmountByRepaymentScheduleId($idRepaymentSchedule), $this->getTaxAmountByRepaymentScheduleId($idRepaymentSchedule), 2);
    }

    /**
     * @param int $idRepaymentSchedule
     *
     * @return mixed
     */
    public function getDetailByRepaymentScheduleId($idRepaymentSchedule)
    {
        $query = '
                SELECT
                  o_capital.amount AS capital,
                  o_interest.amount AS interest,
                  (SELECT SUM(amount) FROM operation INNER JOIN operation_type ON operation.id_type = operation_type.id AND operation_type.label IN ("' . implode('","', OperationType::TAX_TYPES_FR) . '") WHERE operation.id_repayment_schedule = o_interest.id_repayment_schedule) AS taxes,
                  (SELECT available_balance
                    FROM wallet_balance_history wbh 
                    INNER JOIN operation o ON wbh.id_operation = o.id 
                    WHERE o.id_repayment_schedule = o_interest.id_repayment_schedule ANd id_wallet = o_interest.id_wallet_creditor ORDER BY wbh.id DESC LIMIT 1) AS available_balance
                FROM operation o_capital
                  INNER JOIN operation_type ot_capital ON o_capital.id_type = ot_capital.id AND ot_capital.label = "' . OperationType::CAPITAL_REPAYMENT . '"
                  LEFT JOIN operation o_interest ON o_capital.id_repayment_schedule = o_interest.id_repayment_schedule
                  INNER JOIN operation_type ot_interest ON o_interest.id_type = ot_interest.id AND ot_interest.label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '"
                WHERE o_capital.id_repayment_schedule = :idRepaymentSchedule';

        $qcProfile = new QueryCacheProfile(\Unilend\librairies\CacheKeys::DAY, md5(__METHOD__ . $idRepaymentSchedule));
        $statement = $this->getEntityManager()->getConnection()->executeCacheQuery($query, ['idRepaymentSchedule' => $idRepaymentSchedule], ['idRepaymentSchedule' => \PDO::PARAM_INT], $qcProfile);
        $result    = $statement->fetch();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function getInterestAndTaxForFiscalState(\DateTime $start, \DateTime $end)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = 'SELECT
                  CASE c.type
                  WHEN 2 THEN "legal_entity"
                  WHEN 4 THEN "legal_entity"
                  WHEN 1 THEN "person"
                  WHEN 3 THEN "person"
                  END AS client_type,
                  l.id_type_contract,
                  CASE IFNULL((SELECT resident_etranger FROM lenders_imposition_history lih WHERE lih.id_lender = w.id AND lih.added <= e.date_echeance_reel ORDER BY added DESC LIMIT 1), 0)
                    WHEN 0 THEN "fr"
                    ELSE "ww"
                  END AS fiscal_residence,
                  CASE lte.id_lender
                    WHEN e.id_lender THEN "non_taxable"
                    ELSE "taxable"
                  END AS exemption_status,
                  SUM(o_interest.amount) AS interests,
                  SUM(o_tax_fr_prelevements_obligatoires.amount)    AS "' . OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS . '",
                  SUM(o_tax_fr_csg.amount)                          AS "' . OperationType::TAX_FR_CSG . '",
                  SUM(o_tax_fr_prelevements_sociaux.amount)         AS "' . OperationType::TAX_FR_SOCIAL_DEDUCTIONS . '",
                  SUM(o_tax_fr_contributions_additionnelles.amount) AS "' . OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS . '",
                  SUM(o_tax_fr_prelevements_de_solidarite.amount)   AS "' . OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS . '",
                  SUM(o_tax_fr_crds.amount)                         AS "' . OperationType::TAX_FR_CRDS . '",
                  SUM(o_tax_fr_retenues_a_la_source.amount)         AS "' . OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE . '"
                FROM operation o_interest USE INDEX (idx_operation_added)
                  INNER JOIN wallet w ON o_interest.id_wallet_creditor = w.id
                  INNER JOIN clients c ON w.id_client = c.id_client
                  INNER JOIN echeanciers e ON o_interest.id_repayment_schedule = e.id_echeancier
                  INNER JOIN loans l ON l.id_loan = e.id_loan AND l.status = 0
                  LEFT JOIN lender_tax_exemption lte ON lte.id_lender = w.id AND lte.year = YEAR(o_interest.added)
                  LEFT JOIN operation o_tax_fr_contributions_additionnelles
                    ON o_tax_fr_contributions_additionnelles.id_repayment_schedule = o_interest.id_repayment_schedule
                       AND o_tax_fr_contributions_additionnelles.id_wallet_debtor = o_interest.id_wallet_creditor
                       AND o_tax_fr_contributions_additionnelles.id_type = (SELECT id
                                                                            FROM operation_type
                                                                            WHERE label = "' . OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS . '")
                  LEFT JOIN operation o_tax_fr_crds
                    ON o_tax_fr_crds.id_repayment_schedule = o_interest.id_repayment_schedule
                       AND o_tax_fr_crds.id_wallet_debtor = o_interest.id_wallet_creditor
                       AND o_tax_fr_crds.id_type = (SELECT id
                                                    FROM operation_type
                                                    WHERE label = "' . OperationType::TAX_FR_CRDS . '")
                  LEFT JOIN operation o_tax_fr_csg
                    ON o_tax_fr_csg.id_repayment_schedule = o_interest.id_repayment_schedule
                       AND o_tax_fr_csg.id_wallet_debtor = o_interest.id_wallet_creditor
                       AND o_tax_fr_csg.id_type = (SELECT id
                                                   FROM operation_type
                                                   WHERE label = "' . OperationType::TAX_FR_CSG . '")
                  LEFT JOIN operation o_tax_fr_prelevements_de_solidarite
                    ON o_tax_fr_prelevements_de_solidarite.id_repayment_schedule = o_interest.id_repayment_schedule
                       AND o_tax_fr_prelevements_de_solidarite.id_wallet_debtor = o_interest.id_wallet_creditor
                       AND o_tax_fr_prelevements_de_solidarite.id_type = (SELECT id
                                                                          FROM operation_type
                                                                          WHERE label = "' . OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS . '")
                  LEFT JOIN operation o_tax_fr_prelevements_obligatoires
                    ON o_tax_fr_prelevements_obligatoires.id_repayment_schedule = o_interest.id_repayment_schedule
                       AND o_tax_fr_prelevements_obligatoires.id_wallet_debtor = o_interest.id_wallet_creditor
                       AND o_tax_fr_prelevements_obligatoires.id_type = (SELECT id
                                                                         FROM operation_type
                                                                         WHERE label = "' . OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS . '")
                  LEFT JOIN operation o_tax_fr_prelevements_sociaux
                    ON o_tax_fr_prelevements_sociaux.id_repayment_schedule = o_interest.id_repayment_schedule
                       AND o_tax_fr_prelevements_sociaux.id_wallet_debtor = o_interest.id_wallet_creditor
                       AND o_tax_fr_prelevements_sociaux.id_type = (SELECT id
                                                                    FROM operation_type
                                                                    WHERE label = "' . OperationType::TAX_FR_SOCIAL_DEDUCTIONS . '")
                  LEFT JOIN operation o_tax_fr_retenues_a_la_source
                    ON o_tax_fr_retenues_a_la_source.id_repayment_schedule = o_interest.id_repayment_schedule
                       AND o_tax_fr_retenues_a_la_source.id_wallet_debtor = o_interest.id_wallet_creditor
                       AND o_tax_fr_retenues_a_la_source.id_type = (SELECT id
                                                                    FROM operation_type
                                                                    WHERE label = "' . OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE . '")
                
                WHERE o_interest.added BETWEEN :start AND :end
                      AND o_interest.id_type = (SELECT id
                                                FROM operation_type
                                                WHERE label = "' . OperationType::GROSS_INTEREST_REPAYMENT . '")
                GROUP BY l.id_type_contract, client_type, fiscal_residence,  exemption_status';

        return $this->getEntityManager()->getConnection()
            ->executeQuery($query, ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')])
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param Projects|integer $project
     * @param Clients[]        $clients
     *
     * @return float
     */
    public function getTotalGrossDebtCollectionRepayment($project, array $clients)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(o.amount)')
            ->innerJoin('UnilendCoreBusinessBundle:OperationSubType', 'ost', Join::WITH, 'o.idSubType = ost.id')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'w.id = o.idWalletCreditor')
            ->where('ost.label = :operationSubType')
            ->andWhere('w.idClient IN (:clients)')
            ->andWhere('o.idProject = :project')
            ->setParameter('operationSubType', OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION)
            ->setParameter('clients', $clients)
            ->setParameter('project', $project);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return string
     */
    private function getDailyStateQuery()
    {
        return 'SELECT
                  LEFT(o.added, 10) AS day,
                  MONTH(o.added) AS month,
                  SUM(o.amount) AS amount,
                  IF(o.id_sub_type IS NULL,
                     CASE ot.label
                        WHEN "' . OperationType::LENDER_PROVISION . '" THEN
                          IF(o.id_backpayline IS NOT NULL,
                             "lender_provision_credit_card",
                             IF(o.id_wire_transfer_in IS NOT NULL,
                                "lender_provision_wire_transfer_in",
                                NULL)
                          )
                     ELSE ot.label END,
                     ost.label)  AS movement
                FROM operation o USE INDEX (idx_operation_added)
                INNER JOIN operation_type ot ON o.id_type = ot.id
                LEFT JOIN operation_sub_type ost ON o.id_sub_type = ost.id';
    }

    /**
 * @param \DateTime $start
 * @param \DateTime $end
 *
 * @return array
 */
    public function sumMovementsForDailyStateByDay(\DateTime $start, \DateTime $end, array $operationTypes)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = $this->getDailyStateQuery() . ' 
                    WHERE o.added BETWEEN :start AND :end
                    AND ot.label IN ("' . implode('","', $operationTypes) . '")
                    GROUP BY day, movement
                    ORDER BY o.added ASC';

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query, ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')])
            ->fetchAll(\PDO::FETCH_ASSOC);

        $movements = [];
        foreach ($result as $row) {
            $movements[$row['day']][$row['movement']] = $row['amount'];
        }

        return $movements;
    }

    /**
     * @param \DateTime $requestedDate
     * @param array     $operationTypes
     *
     * @return array
     */
    public function sumMovementsForDailyStateByMonth(\DateTime $requestedDate, array $operationTypes)
    {
        $start = new \DateTime('First day of january ' . $requestedDate->format('Y'));
        $start->setTime(0, 0, 0);
        $requestedDate->setTime(23, 59, 59);

        $query = $this->getDailyStateQuery() . ' 
                    WHERE o.added BETWEEN :start AND :end
                      AND ot.label IN ("' . implode('","', $operationTypes) . '")
                    GROUP BY month, movement
                    ORDER BY o.added ASC';

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query, ['start' => $start->format('Y-m-d H:i:s'), 'end' => $requestedDate->format('Y-m-d H:i:s')])
            ->fetchAll(\PDO::FETCH_ASSOC);

        $movements = [];
        foreach ($result as $row) {
            $movements[$row['month']][$row['movement']] = $row['amount'];
        }

        return $movements;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function sumMovementsForDailyState(\DateTime $start, \DateTime $end, array $operationTypes)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = $this->getDailyStateQuery() . ' 
                    WHERE o.added BETWEEN :start AND :end
                    AND ot.label IN ("' . implode('","', $operationTypes) . '")
                    GROUP BY movement
                    ORDER BY o.added ASC';

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query, ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')])
            ->fetchAll(\PDO::FETCH_ASSOC);

        $movements = [];
        foreach ($result as $row) {
            $movements[$row['movement']] = $row['amount'];
        }

        return $movements;
    }

    /**
     * @return string
     */
    private function getCohortQuery()
    {
        return 'SELECT
                  CASE LEFT(MIN(psh.added), 4)
                  WHEN 2013 THEN "2013-2014"
                  WHEN 2014 THEN "2013-2014"
                  ELSE LEFT(psh.added, 4)
                  END AS date_range
                FROM projects_status_history psh
                  INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . ' AND o.id_project = psh.id_project
                GROUP BY psh.id_project';
    }

    /**
     * @param $repaymentType
     *
     * @return array
     */
    public function getTotalRepaymentByCohort($repaymentType)
    {
        $query = 'SELECT SUM(o.amount) AS amount, ( ' . $this->getCohortQuery() . ' ) AS cohort
                  FROM operation o
                  WHERE o.id_type = (SELECT id FROM operation_type WHERE label = :repayment_type)
                  GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, ['repayment_type' => $repaymentType]);
        $result    = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param boolean $isHealthy
     *
     * @return array
     */
    public function getTotalDebtCollectionRepaymentByCohort($isHealthy)
    {
        $query = 'SELECT SUM(o.amount) AS amount, ( ' . $this->getCohortQuery() . ' ) AS cohort
                  FROM operation o
                  INNER JOIN projects p ON o.id_project = p.id_project
                  WHERE o.id_sub_type = (SELECT id FROM operation_sub_type WHERE label = :capital_repayment_debt_collection)
                  AND IF (
                      p.status IN (130,140,150,160)
                      OR (p.status IN (100,110,120)
                          AND DATEDIFF(NOW(), (
                        SELECT psh2.added
                        FROM projects_status_history psh2
                          INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                        WHERE ps2.status = 100
                              AND psh2.id_project = o.id_project
                        ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                        LIMIT 1)) > 180), FALSE, TRUE) = :isHealthy
                  GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()->executeQuery(
            $query,
            ['isHealthy' => $isHealthy, 'capital_repayment_debt_collection' => OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION]
        );
        $result    = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param boolean $isHealthy
     *
     * @return array
     */
    public function getTotalDebtCollectionLenderCommissionByCohort($isHealthy)
    {
        $query = 'SELECT SUM(o.amount) AS amount, ( ' . $this->getCohortQuery() . ' ) AS cohort
                  FROM operation o
                  INNER JOIN projects p ON o.id_project = p.id_project
                  WHERE o.id_type = (SELECT id FROM operation_type WHERE label = :collection_commission_lender)
                  AND IF (
                      p.status IN (130,140,150,160)
                      OR (p.status IN (100,110,120)
                          AND DATEDIFF(NOW(), (
                        SELECT psh2.added
                        FROM projects_status_history psh2
                          INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                        WHERE ps2.status = 100
                              AND psh2.id_project = o.id_project
                        ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                        LIMIT 1)) > 180), FALSE, TRUE) = :isHealthy
                  GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()->executeQuery(
            $query,
            ['isHealthy' => $isHealthy, 'collection_commission_lender' => OperationType::COLLECTION_COMMISSION_LENDER]
        );
        $result    = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param Loans|integer $loan
     *
     * @return float
     */
    public function getTotalEarlyRepaymentByLoan($loan)
    {
        $queryBuilder = $this->createQueryBuilder('o');
        $queryBuilder->select('SUM(o.amount)')
            ->innerJoin('UnilendCoreBusinessBundle:OperationSubType', 'ost', Join::WITH, 'o.idSubType = ost.id')
            ->where('ost.label = :earlyRepayment')
            ->andWhere('o.idLoan = :loan')
            ->setParameter('earlyRepayment', OperationSubType::CAPITAL_REPAYMENT_EARLY)
            ->setParameter('loan', $loan);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
