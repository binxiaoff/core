<?php

namespace Unilend\Repository;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{Clients, ClientsStatus, ClientsStatusHistory, CompanyStatus, Echeanciers, Loans, Operation, OperationSubType, OperationType, Pays, ProjectRepaymentTask, ProjectRepaymentTaskLog,
    Projects, ProjectsStatus, Receptions, Sponsorship, SponsorshipCampaign, UnilendStats, Wallet};
use Unilend\CacheKeys;

class OperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operation::class);
    }

    public function getOperationByTypeAndAmount($typeLabel, $amount)
    {
        $criteria = [
            'idType' => $this->getEntityManager()->getRepository(OperationType::class)->findOneBy(['label' => $typeLabel]),
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
            'idType'         => $this->getEntityManager()->getRepository(OperationType::class)->findOneBy(['label' => OperationType::LENDER_WITHDRAW]),
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
     * @return integer
     */
    public function getWithdrawAndProvisionOperationByDateAndWallet(Wallet $wallet, \DateTime $date)
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.idType IN (:walletType)')
            ->setParameter('walletType', [
                $this->getEntityManager()->getRepository(OperationType::class)->findOneBy(['label' => OperationType::LENDER_WITHDRAW])->getId(),
                $this->getEntityManager()->getRepository(OperationType::class)->findOneBy(['label' => OperationType::LENDER_PROVISION])->getId(),
            ])
            ->andWhere('o.idWalletCreditor = :idWallet OR o.idWalletDebtor = :idWallet')
            ->setParameter('idWallet', $wallet)
            ->andWhere('o.added >= :added')
            ->setParameter('added', $date);

        return $qb->getQuery()->getSingleScalarResult();
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

        $queryProjects = '
                        SELECT DISTINCT (p.id_project) FROM projects p
                        INNER JOIN projects_status_history psh ON p.id_project = psh.id_project
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                        WHERE ps.status = ' . ProjectsStatus::STATUS_REPAYMENT . '
                        AND psh.added <= :end
                        AND p.status != ' . ProjectsStatus::STATUS_LOSS;

        $query = '
            SELECT IFNULL(SUM(o_loan.amount), 0) - (
              SELECT IFNULL(SUM(o_repayment.amount), 0)
              FROM operation o_repayment
              INNER JOIN operation_type ot ON ot.id = o_repayment.id_type
              WHERE o_repayment.added <= :end
              AND ot.label = \'' . OperationType::CAPITAL_REPAYMENT . '\'
              AND o_repayment.id_wallet_creditor = o_loan.id_wallet_debtor
              AND o_repayment.id_project IN (' . $queryProjects . ')
            ) - (
              SELECT IFNULL(SUM(o_repayment_regul.amount), 0)
              FROM operation o_repayment_regul
              INNER JOIN operation_type ot ON ot.id = o_repayment_regul.id_type
              WHERE o_repayment_regul.added <= :end
              AND ot.label = \'' . OperationType::CAPITAL_REPAYMENT_REGULARIZATION . '\'
              AND o_repayment_regul.id_wallet_debtor = o_loan.id_wallet_debtor
              AND o_repayment_regul.id_project IN (' . $queryProjects . ')
            )
            FROM operation o_loan
            INNER JOIN wallet w ON w.id = o_loan.id_wallet_debtor
            INNER JOIN operation_type ot ON ot.id = o_loan.id_type
            WHERE o_loan.added <= :end
            AND ot.label = \'' . OperationType::LENDER_LOAN . '\'
            AND o_loan.id_project  IN (' . $queryProjects . ')
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
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:operationTypes)')
            ->andWhere('o.idWalletCreditor = :idWallet')
            ->setParameter('operationTypes', $operationTypes, Connection::PARAM_STR_ARRAY)
            ->setParameter('idWallet', $creditorWallet->getId());

        if (null !== $operationSubTypes) {
            $qb->innerJoin(OperationSubType::class, 'ost', Join::WITH, 'o.idSubType = ost.id')
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
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:operationTypes)')
            ->andWhere('o.idWalletDebtor = :idWallet')
            ->setParameter('operationTypes', $operationTypes, Connection::PARAM_STR_ARRAY)
            ->setParameter('idWallet', $debtorWallet->getId());

        if (null !== $operationSubTypes) {
            $qb->innerJoin(OperationSubType::class, 'ost', Join::WITH, 'o.idSubType = ost.id')
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
                     SUM(IF(tlih.id_pays IN (:eeaCountries), o_capital.amount, 0)) AS capital
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
            'eeaCountries' => Pays::EUROPEAN_ECONOMIC_AREA
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
    public function sumRegularizedCapitalRepaymentsInEeaExceptFrance(Wallet $wallet, $year)
    {
        $query = 'SELECT
                     SUM(IF(tlih.id_pays IN (:eeaCountries), o_capital.amount, 0)) AS capital
                  FROM operation o_capital
                     LEFT JOIN (SELECT
                                  o.id_wallet_debtor,
                                  o.id,
                                  (SELECT lih.id_pays
                                   FROM lenders_imposition_history lih
                                   WHERE id_lender = o.id_wallet_debtor AND DATE(lih.added) <= DATE(o.added)
                                   ORDER BY lih.added DESC
                                   LIMIT 1) AS id_pays
                                FROM operation o
                                  INNER JOIN operation_type ot ON o.id_type = ot.id
                                WHERE ot.label = "' . OperationType::CAPITAL_REPAYMENT_REGULARIZATION . '" AND o.id_wallet_debtor = :idWallet) AS tlih ON o_capital.id = tlih.id
                    WHERE o_capital.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::CAPITAL_REPAYMENT_REGULARIZATION . '")
                    AND LEFT(o_capital.added, 4) = :year
                    AND o_capital.id_wallet_debtor = :idWallet';

        $bind  = [
            'year'         => $year,
            'idWallet'     => $wallet->getId(),
            'eeaCountries' => Pays::EUROPEAN_ECONOMIC_AREA
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
            'eeaCountries'  => Pays::EUROPEAN_ECONOMIC_AREA
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
    public function sumRegularizedNetInterestRepaymentsNotInEeaExceptFrance(Wallet $wallet, $year)
    {
        $query = '
            SELECT SUM(IF(tlih.id_pays IN (:eeaCountries), o_interest.amount, 0)) AS interest
            FROM operation o_interest
            LEFT JOIN (
              SELECT 
                SUM(amount) AS amount,
                id_repayment_schedule
              FROM operation
              WHERE operation.id_wallet_debtor = :idWallet
                AND operation.id_type IN (SELECT id FROM operation_type WHERE label IN (:taxOperations))
              GROUP BY id_repayment_schedule
            ) AS o_taxes ON o_interest.id_repayment_schedule = o_taxes.id_repayment_schedule
            LEFT JOIN (
              SELECT
                o.id_wallet_debtor,
                o.id,
                (
                  SELECT lih.id_pays
                  FROM lenders_imposition_history lih
                  WHERE id_lender = o.id_wallet_debtor AND DATE(lih.added) <= DATE(o.added)
                  ORDER BY lih.added DESC
                  LIMIT 1
                ) AS id_pays
              FROM operation o
                INNER JOIN operation_type ot ON o.id_type = ot.id
              WHERE ot.label = "' . OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION . '" AND o.id_wallet_debtor = :idWallet
            ) AS tlih ON o_interest.id = tlih.id
            WHERE o_interest.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION . '")
              AND LEFT(o_interest.added, 4) = :year
              AND o_interest.id_wallet_debtor = :idWallet';

        $bind = [
            'year'          => $year,
            'idWallet'      => $wallet->getId(),
            'taxOperations' => OperationType::TAX_TYPES_FR,
            'eeaCountries'  => Pays::EUROPEAN_ECONOMIC_AREA
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
     * @param Wallet $wallet
     * @param int    $year
     *
     * @return bool|string
     */
    public function getRegularizedGrossInterestPaymentsInFrance(Wallet $wallet, $year)
    {
        $query = 'SELECT
                      SUM(IF(tlih.id_pays = 1 OR tlih.id_pays IS NULL OR tlih.id_pays = "", o_interest.amount, 0)) AS sum66
                    FROM operation o_interest
                      LEFT JOIN (SELECT
                                   o.id_wallet_debtor,
                                   o.id,
                                   (SELECT lih.id_pays
                                    FROM lenders_imposition_history lih
                                      WHERE id_lender = o.id_wallet_debtor AND added <= o.added
                                    ORDER BY added DESC
                                    LIMIT 1) AS id_pays
                                 FROM operation o
                                   INNER JOIN operation_type ot ON o.id_type = ot.id
                                 WHERE ot.label = "' . OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION . '" AND o.id_wallet_debtor = :idWallet) AS tlih ON o_interest.id = tlih.id
                    WHERE o_interest.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION . '")
                      AND LEFT(o_interest.added, 4) = :year 
                      AND o_interest.id_wallet_debtor =  :idWallet';

        return $this->getEntityManager()->getConnection()->executeQuery($query, ['year' => $year, 'idWallet' => $wallet->getId()])->fetchColumn();
    }

    /**
     * @param Echeanciers|int $idRepaymentSchedule
     *
     * @return float
     */
    public function getTaxAmountByRepaymentScheduleId($idRepaymentSchedule)
    {
        $qbRegularization = $this->createQueryBuilder('o_r');
        $qbRegularization->select('IFNULL(SUM(o_r.amount), 0)')
            ->innerJoin(OperationType::class, 'ot_r', Join::WITH, 'o_r.idType = ot_r.id')
            ->where('ot_r.label IN (:taxRegularizationTypes)')
            ->andWhere('o_r.idRepaymentSchedule = :idRepaymentSchedule');
        $regularization = $qbRegularization->getDQL();

        $qb = $this->createQueryBuilder('o');
        $qb->select('IFNULL(SUM(o.amount), 0) as amount')
            ->addSelect('(' . $regularization . ') as regularized_amount')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:taxTypes)')
            ->andWhere('o.idRepaymentSchedule = :idRepaymentSchedule')
            ->setParameter('taxTypes', OperationType::TAX_TYPES_FR, Connection::PARAM_STR_ARRAY)
            ->setParameter('taxRegularizationTypes', OperationType::TAX_TYPES_FR_REGULARIZATION, Connection::PARAM_STR_ARRAY)
            ->setParameter('idRepaymentSchedule', $idRepaymentSchedule)
            ->setCacheable(true);

        $result = $qb->getQuery()->getArrayResult();

        return round(bcsub($result[0]['amount'], $result[0]['regularized_amount'], 4), 2);
    }

    /**
     * @param Echeanciers|int $idRepaymentSchedule
     *
     * @return float
     */
    public function getGrossAmountByRepaymentScheduleId($idRepaymentSchedule)
    {
        $qbRegularization = $this->createQueryBuilder('o_r');
        $qbRegularization->select('IFNULL(SUM(o_r.amount), 0)')
            ->innerJoin(OperationType::class, 'ot_r', Join::WITH, 'o_r.idType = ot_r.id')
            ->where('ot_r.label IN (:repaymentRegularizationTypes)')
            ->andWhere('o_r.idRepaymentSchedule = :idRepaymentSchedule');
        $regularization = $qbRegularization->getDQL();

        $qb = $this->createQueryBuilder('o');
        $qb->select('IFNULL(SUM(o.amount), 0) as amount')
            ->addSelect('(' . $regularization . ') as regularized_amount')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:repaymentTypes)')
            ->andWhere('o.idRepaymentSchedule = :idRepaymentSchedule')
            ->setParameter('repaymentTypes', [OperationType::CAPITAL_REPAYMENT, OperationType::GROSS_INTEREST_REPAYMENT], Connection::PARAM_STR_ARRAY)
            ->setParameter('repaymentRegularizationTypes', [OperationType::CAPITAL_REPAYMENT_REGULARIZATION, OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION], Connection::PARAM_STR_ARRAY)
            ->setParameter('idRepaymentSchedule', $idRepaymentSchedule)
            ->setCacheable(true);

        $result = $qb->getQuery()->getArrayResult();

        return round(bcsub($result[0]['amount'], $result[0]['regularized_amount'], 4), 2);
    }

    /**
     * @param Echeanciers|int $idRepaymentSchedule
     *
     * @return null|float
     */
    public function getNetAmountByRepaymentScheduleId($idRepaymentSchedule)
    {
        return round(bcsub($this->getGrossAmountByRepaymentScheduleId($idRepaymentSchedule), $this->getTaxAmountByRepaymentScheduleId($idRepaymentSchedule), 4), 2);
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool      $regularization
     *
     * @return array
     */
    public function getInterestFiscalState(\DateTime $start, \DateTime $end, $regularization = false)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $interestOperationType = OperationType::GROSS_INTEREST_REPAYMENT;
        $walletField           = 'id_wallet_creditor';
        if ($regularization) {
            $interestOperationType = OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION;
            $walletField           = 'id_wallet_debtor';
        }
        $query = 'SELECT
                  CASE c.type
                  WHEN 2 THEN "legal_entity"
                  WHEN 4 THEN "legal_entity"
                  WHEN 1 THEN "person"
                  WHEN 3 THEN "person"
                  END AS client_type,
                  l.id_type_contract,
                  CASE IFNULL((SELECT resident_etranger FROM lenders_imposition_history lih WHERE lih.id_lender = w.id AND lih.added <= o_interest.added ORDER BY added DESC LIMIT 1), 0)
                    WHEN 0 THEN "fr"
                    ELSE "ww"
                  END AS fiscal_residence,
                  CASE lte.id_lender
                    WHEN o_interest.' . $walletField . ' THEN "non_taxable"
                    ELSE "taxable"
                  END AS exemption_status,
                  SUM(o_interest.amount) AS interests
                FROM operation o_interest USE INDEX (idx_operation_added)
                  INNER JOIN operation_type ot_interest ON o_interest.id_type = ot_interest.id AND ot_interest.label = "' . $interestOperationType . '"
                  INNER JOIN wallet w ON o_interest.' . $walletField . ' = w.id
                  INNER JOIN clients c ON w.id_client = c.id_client
                  INNER JOIN loans l ON l.id_loan = o_interest.id_loan
                  LEFT JOIN lender_tax_exemption lte ON lte.id_lender = w.id AND lte.year = YEAR(o_interest.added)
                WHERE o_interest.added BETWEEN :start AND :end
                GROUP BY l.id_type_contract, client_type, fiscal_residence,  exemption_status';

        return $this->getEntityManager()->getConnection()
            ->executeQuery($query, ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')])
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string      $taxOperationType
     * @param null|string $taxOperationSubType
     * @param \DateTime   $start
     * @param \DateTime   $end
     * @param bool        $groupByContract
     * @param bool        $regularization
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTaxForFiscalState(string $taxOperationType, ?string $taxOperationSubType, \DateTime $start, \DateTime $end, bool $groupByContract = false, bool $regularization = false): array
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        if ($regularization) {
            $taxOperationType = $taxOperationType . '_REGULARIZATION';
        }

        $contractLabelColumn = $groupByContract ? ', uc.label as contract_label' : '';
        $groupBy             = $groupByContract ? ' GROUP BY l.id_type_contract' : '';

        $query = 'SELECT SUM(o_tax.amount) AS tax ' . $contractLabelColumn . '
                  FROM operation o_tax USE INDEX (idx_operation_added)
                    INNER JOIN operation_type ot_tax ON ot_tax.id = o_tax.id_type
                    LEFT JOIN operation_sub_type ost_tax ON ost_tax.id = o_tax.id_sub_type
                    INNER JOIN loans l ON l.id_loan = o_tax.id_loan
                    INNER JOIN underlying_contract uc ON uc.id_contract = l.id_type_contract
                  WHERE o_tax.added BETWEEN :start AND :end
                    AND ot_tax.label = :taxOperationType';

        $parameters = [
            'start'            => $start->format('Y-m-d H:i:s'),
            'end'              => $end->format('Y-m-d H:i:s'),
            'taxOperationType' => $taxOperationType
        ];

        if (null === $taxOperationSubType) {
            $query .= ' AND o_tax.id_sub_type IS NULL';
        } else {
            $query                             .= ' AND ost_tax.label = :taxOperationSubType';
            $parameters['taxOperationSubType'] = $taxOperationSubType;
        }

        $query .= $groupBy;

        return $this->getEntityManager()->getConnection()
            ->executeQuery($query, $parameters)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Used in fiscal status to check if we are not apply the tax on exempted lender. It should always return 0.
     *
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool      $regularization
     *
     * @return float
     */
    public function getExemptedIncomeTax(\DateTime $start, \DateTime $end, $regularization = false)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $taxOperationType = OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES;
        if ($regularization) {
            $taxOperationType = OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES_REGULARIZATION;
        }

        $query = '  SELECT IFNULL(SUM(o_interest.amount), 0) AS tax
                    FROM operation o_interest USE INDEX (idx_operation_added)
                      INNER JOIN operation_type ot_interest ON o_interest.id_type = ot_interest.id AND ot_interest.label = "' . $taxOperationType . '"
                      INNER JOIN wallet w ON o_interest.id_wallet_creditor = w.id
                      LEFT JOIN lender_tax_exemption lte ON lte.id_lender = w.id AND lte.year = YEAR(o_interest.added)
                    WHERE o_interest.added BETWEEN :start AND :end
                      AND lte.id_lender IS NOT NULL';

        return $this->getEntityManager()->getConnection()
            ->executeQuery($query, ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')])
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Todo: it can be deleted after the release of "recouvrement"
     *
     * @param Projects|integer $project
     * @param Clients[]        $clients
     *
     * @return float
     */
    public function getTotalGrossDebtCollectionRepayment($project, array $clients)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(
            CASE WHEN ost.label in (:normalDebtCollectionRepayment) THEN IFNULL(o.amount, 0)
            ELSE IFNULL(- o.amount, 0) END
        ) as amount')
            ->innerJoin(OperationSubType::class, 'ost', Join::WITH, 'o.idSubType = ost.id')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'w.id = o.idWalletCreditor OR w.id = o.idWalletDebtor')
            ->where('ost.label IN (:allDebtCollectionRepayment)')
            ->andWhere('w.idClient IN (:clients)')
            ->andWhere('o.idProject = :project')
            ->setParameter('allDebtCollectionRepayment', [
                OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION,
                OperationSubType::GROSS_INTEREST_REPAYMENT_DEBT_COLLECTION,
                OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION_REGULARIZATION,
                OperationSubType::GROSS_INTEREST_REPAYMENT_DEBT_COLLECTION_REGULARIZATION
            ])
            ->setParameter('normalDebtCollectionRepayment', [
                OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION,
                OperationSubType::GROSS_INTEREST_REPAYMENT_DEBT_COLLECTION
            ])
            ->setParameter('clients', $clients)
            ->setParameter('project', $project);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string|null $typeGroupBy
     *
     * @return string
     */
    private function getDailyStateQuery(string $typeGroupBy = null): string
    {
        if ('day' === $typeGroupBy) {
            $select =
                'SELECT
                  LEFT(o.added, 10) AS day,';
        } elseif ('month' === $typeGroupBy) {
            $select = '
                SELECT
                  MONTH(o.added) AS month,';
        } else {
            $select = 'SELECT ';
        }

        $select .=
            'SUM(o.amount) AS amount,
                 CASE ot.label
                    WHEN "' . OperationType::LENDER_PROVISION . '" THEN
                      IF(o.id_backpayline IS NOT NULL,
                         "lender_provision_credit_card",
                         IF(o.id_wire_transfer_in IS NOT NULL,
                            "lender_provision_wire_transfer_in",
                            NULL)
                      )
                    WHEN "' . OperationType::LENDER_PROVISION_CANCEL . '" THEN
                      IF(o.id_backpayline IS NOT NULL,
                         "lender_provision_cancel_credit_card",
                         IF(o.id_wire_transfer_in IS NOT NULL,
                            "lender_provision_cancel_wire_transfer_in",
                            NULL)
                      )
                    WHEN "' . OperationType::BORROWER_COMMISSION . '" THEN ost.label
                    WHEN "' . OperationType::BORROWER_COMMISSION_REGULARIZATION . '" THEN ost.label
                    WHEN "' . OperationType::BORROWER_PROVISION . '" THEN
                      IF(r.type = ' . Receptions::TYPE_DIRECT_DEBIT . ',
                      "borrower_provision_direct_debit",
                        IF(r.type = ' . Receptions::TYPE_WIRE_TRANSFER . ',
                        "borrower_provision_wire_transfer_in",
                        "borrower_provision_other")
                      )
                    WHEN "' . OperationType::BORROWER_PROVISION_CANCEL . '" THEN
                      IF(r.type = ' . Receptions::TYPE_DIRECT_DEBIT . ',
                      "borrower_provision_cancel_direct_debit",
                        IF(r.type = ' . Receptions::TYPE_WIRE_TRANSFER . ',
                        "borrower_provision_cancel_wire_transfer_in",
                        "borrower_provision_cancel_other")
                      )
                    WHEN "' . OperationType::BORROWER_WITHDRAW . '" THEN
                      IF(o.id_sub_type IS NULL, ot.label, ost.label)
                 ELSE ot.label END AS movement
            FROM operation o USE INDEX (idx_operation_added)
            INNER JOIN operation_type ot ON o.id_type = ot.id
            LEFT JOIN operation_sub_type ost ON o.id_sub_type = ost.id
            LEFT JOIN receptions r ON o.id_wire_transfer_in = r.id_reception';

        return $select;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param array     $operationTypes
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function sumMovementsForDailyStateByDay(\DateTime $start, \DateTime $end, array $operationTypes): array
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = $this->getDailyStateQuery('day') . ' 
                    WHERE o.added BETWEEN :start AND :end
                    AND ot.label IN ("' . implode('","', $operationTypes) . '")
                    GROUP BY day, movement
                    ORDER BY day ASC';

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
     * @throws \Doctrine\DBAL\DBALException
     */
    public function sumMovementsForDailyStateByMonth(\DateTime $requestedDate, array $operationTypes): array
    {
        $start = new \DateTime('First day of january ' . $requestedDate->format('Y'));
        $start->setTime(0, 0, 0);
        $requestedDate->setTime(23, 59, 59);

        $query = $this->getDailyStateQuery('month') . ' 
                    WHERE o.added BETWEEN :start AND :end
                      AND ot.label IN ("' . implode('","', $operationTypes) . '")
                    GROUP BY month, movement
                    ORDER BY month ASC';

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
     * @param array     $operationTypes
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function sumMovementsForDailyState(\DateTime $start, \DateTime $end, array $operationTypes)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = $this->getDailyStateQuery() . ' 
                    WHERE o.added BETWEEN :start AND :end
                    AND ot.label IN ("' . implode('","', $operationTypes) . '")
                    GROUP BY movement';

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
     * @param bool $groupFirstYears
     *
     * @return string
     */
    private function getCohortQuery($groupFirstYears)
    {
        if ($groupFirstYears) {
            $cohortSelect = 'CASE LEFT(psh.added, 4)
                                WHEN 2013 THEN "2013-2014"
                                WHEN 2014 THEN "2013-2014"
                                ELSE LEFT(psh.added, 4)
                             END';
        } else {
            $cohortSelect = 'LEFT(psh.added, 4)';
        }

        return 'SELECT ' . $cohortSelect . ' AS date_range
                FROM projects_status_history psh
                  INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE ps.status = ' . ProjectsStatus::STATUS_REPAYMENT . ' AND o.id_project = psh.id_project
                GROUP BY psh.id_project';
    }

    /**
     * @param bool $repaymentType
     * @param bool $groupFirstYears
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTotalRepaymentByCohort($repaymentType, $groupFirstYears = true)
    {
        $query = 'SELECT SUM(o.amount) AS amount, ( ' . $this->getCohortQuery($groupFirstYears) . ' ) AS cohort
                  FROM operation o
                  WHERE o.id_type = (SELECT id FROM operation_type WHERE label = :repayment_type)
                  GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, ['repayment_type' => $repaymentType]);
        $result    = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param bool $isHealthy
     * @param bool $groupFirstYears
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTotalDebtCollectionRepaymentByCohort(bool $isHealthy, bool $groupFirstYears = true): array
    {
        $query = '
            SELECT SUM(o.amount) AS amount, ( ' . $this->getCohortQuery($groupFirstYears) . ' ) AS cohort
            FROM operation o
            INNER JOIN operation_sub_type ost ON o.id_sub_type = ost.id
            INNER JOIN projects p ON o.id_project = p.id_project
            INNER JOIN companies c ON p.id_company = c.id_company
            INNER JOIN company_status cs ON cs.id = c.id_status
            WHERE ost.label = :capital_repayment_debt_collection
            AND IF (
                p.status = :statusLoss
                OR (p.status = :statusProblem AND cs.label IN (:companyStatus))
                OR (p.status = :statusProblem AND cs.label = :inBonis
                  AND DATEDIFF(NOW(), (
                  SELECT psh2.added
                  FROM projects_status_history psh2
                    INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                  WHERE ps2.status = :statusProblem
                        AND psh2.id_project = o.id_project
                  ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                  LIMIT 1)) > ' . UnilendStats::DAYS_AFTER_LAST_PROBLEM_STATUS_FOR_STATISTIC_LOSS . '), FALSE, TRUE) = :isHealthy
            GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()
            ->executeQuery(
                $query,
                [
                    'isHealthy'                         => $isHealthy,
                    'capital_repayment_debt_collection' => OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION,
                    'statusLoss'                        => ProjectsStatus::STATUS_LOSS,
                    'statusProblem'                     => ProjectsStatus::STATUS_LOSS,
                    'companyStatus'                     => [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION],
                    'inBonis'                           => CompanyStatus::STATUS_IN_BONIS
                ],
                ['companyStatus' => Connection::PARAM_STR_ARRAY]
            );
        $result    = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param bool $isHealthy
     * @param bool $groupFirstYears
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTotalDebtCollectionLenderCommissionByCohort(bool $isHealthy, bool $groupFirstYears = true): array
    {
        $query = '
            SELECT SUM(o.amount) AS amount, ( ' . $this->getCohortQuery($groupFirstYears) . ' ) AS cohort
            FROM operation o
            INNER JOIN operation_type ot ON o.id_type = ot.id
            INNER JOIN projects p ON o.id_project = p.id_project
            INNER JOIN companies c ON p.id_company = c.id_company
            INNER JOIN company_status cs ON cs.id = c.id_status
            WHERE ot.label = :collection_commission_lender
            AND IF (
                p.status = :statusLoss
                OR (p.status = :statusProblem AND cs.label IN (:companyStatus))
                OR (p.status = :statusProblem
                    AND DATEDIFF(NOW(), (
                  SELECT psh2.added
                  FROM projects_status_history psh2
                    INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                  WHERE ps2.status = :statusProblem
                        AND psh2.id_project = o.id_project
                  ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                  LIMIT 1)) > ' . UnilendStats::DAYS_AFTER_LAST_PROBLEM_STATUS_FOR_STATISTIC_LOSS . '), FALSE, TRUE) = :isHealthy
            GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()
            ->executeQuery(
                $query,
                [
                    'isHealthy'                    => $isHealthy,
                    'collection_commission_lender' => OperationType::COLLECTION_COMMISSION_LENDER,
                    'statusLoss'                   => ProjectsStatus::STATUS_LOSS,
                    'statusProblem'                => ProjectsStatus::STATUS_LOSS,
                    'companyStatus'                => [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION],
                    'inBonis'                      => CompanyStatus::STATUS_IN_BONIS
                ],
                ['companyStatus' => Connection::PARAM_STR_ARRAY]
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
            ->innerJoin(OperationSubType::class, 'ost', Join::WITH, 'o.idSubType = ost.id')
            ->where('ost.label = :earlyRepayment')
            ->andWhere('o.idLoan = :loan')
            ->setParameter('earlyRepayment', OperationSubType::CAPITAL_REPAYMENT_EARLY)
            ->setParameter('loan', $loan);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int|null $loan
     * @param int      $wallet
     * @param int      $repaymentTaskLogId
     *
     * @return mixed
     */
    public function getDetailByLoanAndRepaymentLog($loan, $wallet, $repaymentTaskLogId)
    {
        $query = 'SELECT
                  SUM(IF(ot.label IN (:capitalRepaymentLabel), o.amount, 0))       AS capital,
                  SUM(IF(ot.label IN (:grossInterestRepaymentLabel), o.amount, 0)) AS interest,
                  SUM(IF(ot.label IN (:frenchTaxes), o.amount, 0))                 AS taxes,
                  NULL                                                             AS available_balance,
                  MIN(o.added)                                                     AS added
                FROM operation o
                  INNER JOIN operation_type ot ON ot.id = o.id_type
                WHERE o.id_repayment_task_log = :repaymentTaskLogId';

        // For legacy debt collection repayment compatibility.
        if (empty($loan)) {
            $query                .= ' AND (o.id_wallet_creditor = :wallet OR o.id_wallet_debtor = :wallet)';
            $parameters['wallet'] = $wallet;
        } else {
            $query              .= ' AND o.id_loan = :loan';
            $parameters['loan'] = $loan;
        }

        $qcProfile  = new QueryCacheProfile(CacheKeys::DAY, md5(__METHOD__));
        $parameters = array_merge($parameters, [
            'capitalRepaymentLabel'       => [OperationType::CAPITAL_REPAYMENT, OperationType::CAPITAL_REPAYMENT_REGULARIZATION],
            'grossInterestRepaymentLabel' => [OperationType::GROSS_INTEREST_REPAYMENT, OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION],
            'frenchTaxes'                 => array_merge(OperationType::TAX_TYPES_FR, OperationType::TAX_TYPES_FR_REGULARIZATION),
            'repaymentTaskLogId'          => $repaymentTaskLogId
        ]);
        $types      = ['capitalRepaymentLabel' => Connection::PARAM_STR_ARRAY, 'grossInterestRepaymentLabel' => Connection::PARAM_STR_ARRAY, 'frenchTaxes' => Connection::PARAM_STR_ARRAY];
        $statement  = $this->getEntityManager()->getConnection()->executeQuery($query, $parameters, $types, $qcProfile);
        $result     = $statement->fetch();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param Wallet         $creditorWallet
     * @param array          $operationTypes
     * @param array|null     $operationSubTypes
     * @param \DateTime|null $end
     *
     * @return mixed
     */
    public function sumCreditOperationsByTypeUntil(Wallet $creditorWallet, $operationTypes, $operationSubTypes = null, \DateTime $end = null)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(o.amount)')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:operationTypes)')
            ->andWhere('o.idWalletCreditor = :idWallet')
            ->setParameter('operationTypes', $operationTypes, Connection::PARAM_STR_ARRAY)
            ->setParameter('idWallet', $creditorWallet->getId());

        if (null !== $operationSubTypes) {
            $qb->innerJoin(OperationSubType::class, 'ost', Join::WITH, 'o.idSubType = ost.id')
                ->andWhere('ost.label IN (:operationSubTypes)')
                ->setParameter('operationSubTypes', $operationSubTypes, Connection::PARAM_STR_ARRAY);
        }

        if (null !== $end) {
            $end->setTime(23, 59, 59);
            $qb->andWhere('o.added <= :end')->setParameter('end', $end);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /***
     * @param Wallet         $debtorWallet
     * @param array          $operationTypes
     * @param array|null     $operationSubTypes
     * @param \DateTime|null $end
     *
     * @return mixed
     */
    public function sumDebitOperationsByTypeUntil(Wallet $debtorWallet, $operationTypes, $operationSubTypes = null, \DateTime $end = null)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(o.amount)')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:operationTypes)')
            ->andWhere('o.idWalletDebtor = :idWallet')
            ->setParameter('operationTypes', $operationTypes, Connection::PARAM_STR_ARRAY)
            ->setParameter('idWallet', $debtorWallet->getId());

        if (null !== $operationSubTypes) {
            $qb->innerJoin(OperationSubType::class, 'ost', Join::WITH, 'o.idSubType = ost.id')
                ->andWhere('ost.label IN (:operationSubTypes)')
                ->setParameter('operationSubTypes', $operationSubTypes, Connection::PARAM_STR_ARRAY);
        }

        if (null !== $end) {
            $end->setTime(23, 59, 59);
            $qb->andWhere('o.added <= :end')->setParameter('end', $end);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool      $groupByProvision
     * @param bool      $onlineLenders
     *
     * @return array
     */
    public function getLenderProvisionIndicatorsBetweenDates(\DateTime $start, \DateTime $end, $groupByProvision = true, $onlineLenders = true)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $queryBuilder = $this->createQueryBuilder('o');
        $queryBuilder->select('SUM(o.amount) AS totalAmount')
            ->addSelect('COUNT(DISTINCT o.idWalletCreditor) AS numberLenders')
            ->addSelect('ROUND(AVG(o.amount), 2) AS averageAmount')
            ->addSelect('COUNT(o.id) AS numberProvisions')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label = :lenderProvision')
            ->andWhere('o.added BETWEEN :start AND :end')
            ->setParameter('lenderProvision', OperationType::LENDER_PROVISION)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        if ($groupByProvision) {
            $queryBuilder
                ->addSelect('CASE WHEN (o.idBackpayline IS NOT NULL) THEN \'creditCard\' ELSE \'wireTransferIn\' END AS provisionType')
                ->groupBy('provisionType');
        }

        if ($onlineLenders) {
            $queryBuilder
                ->innerJoin(Wallet::class, 'w', Join::WITH, 'o.idWalletCreditor = w.id')
                ->innerJoin(Clients::class, 'c', Join::WITH, 'c.idClient = w.idClient')
                ->innerJoin(ClientsStatusHistory::class, 'csh', Join::WITH, 'c.idClientStatusHistory = csh.id')
                ->andWhere('csh.idStatus IN (:onlineStatus)')
                ->setParameter('onlineStatus', ClientsStatus::GRANTED_LOGIN, Connection::PARAM_INT_ARRAY);
        }

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function getLenderWithdrawIndicatorsBetweenDates(\DateTime $start, \DateTime $end)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $queryBuilder = $this->createQueryBuilder('o');
        $queryBuilder->select('SUM(o.amount) AS totalAmount')
            ->addSelect('AVG(o.amount) AS averageAmount')
            ->addSelect('COUNT(o.id) AS numberWithdraw')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label = :lenderWithdraw')
            ->andWhere('o.added BETWEEN :start AND :end')
            ->setParameter('lenderWithdraw', OperationType::LENDER_WITHDRAW)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        return $queryBuilder->getQuery()->getResult()[0];
    }

    /**
     * @param \DateTime $end
     * @param array     $projects
     *
     * @return bool|string
     */
    public function getRemainingDueCapitalForProjects(\DateTime $end, array $projects)
    {
        $end->setTime(23, 59, 59);

        $query = '
            SELECT SUM(IF(ot.label IN (:lender_loan, :capital_regularization), IFNULL(amount, 0), IFNULL(-amount, 0)))
            FROM operation o USE INDEX (fk_id_project_idx)
              INNER JOIN operation_type ot ON ot.id = o.id_type
            WHERE o.added <= :end
                  AND ot.label IN (:lender_loan, :capital, :capital_regularization)
                  AND o.id_project IN (:projects)';

        $statement = $this
            ->getEntityManager()
            ->getConnection()
            ->executeQuery($query, [
                'end'                    => $end->format('Y-m-d H:i:s'),
                'projects'               => $projects,
                'lender_loan'            => OperationType::LENDER_LOAN,
                'capital'                => OperationType::CAPITAL_REPAYMENT,
                'capital_regularization' => OperationType::CAPITAL_REPAYMENT_REGULARIZATION,
            ], ['projects' => Connection::PARAM_INT_ARRAY]);

        return $statement->fetchColumn();
    }

    /**
     * @param Loans|int                   $loan
     * @param ProjectRepaymentTaskLog|int $projectRepaymentTaskLog
     *
     * @return float
     */
    public function getTaxAmountByLoanAndRepaymentTaskLog($loan, $projectRepaymentTaskLog)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(CASE WHEN ot.label IN (:taxTypes) THEN o.amount ELSE -o.amount END) as amount')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:allTaxTypes)')
            ->andWhere('o.idLoan = :loan')
            ->andWhere('o.idRepaymentTaskLog = :repaymentTaskLog')
            ->setParameter('taxTypes', OperationType::TAX_TYPES_FR)
            ->setParameter('allTaxTypes', array_merge(OperationType::TAX_TYPES_FR, OperationType::TAX_TYPES_FR_REGULARIZATION))
            ->setParameter('loan', $loan)
            ->setParameter('repaymentTaskLog', $projectRepaymentTaskLog)
            ->setCacheable(true);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param string              $subTypeLabel
     * @param SponsorshipCampaign $sponsorshipCampaign
     *
     * @return mixed
     */
    public function getSumRewardAmountByCampaign($subTypeLabel, SponsorshipCampaign $sponsorshipCampaign, Wallet $wallet = null)
    {
        $queryBuilder = $this->createQueryBuilder('o');
        $queryBuilder->select('SUM(o.amount)')
            ->innerJoin(OperationSubType::class, 'ost', Join::WITH, 'ost.id = o.idSubType')
            ->innerJoin(Sponsorship::class, 'ss', Join::WITH, 'ss.id = o.idSponsorship')
            ->where('ost.label = :subTypeLabel')
            ->andWhere('ss.idCampaign = :idCampaign')
            ->setParameter('subTypeLabel', $subTypeLabel)
            ->setParameter('idCampaign', $sponsorshipCampaign);

        if (null !== $wallet) {
            $queryBuilder->andWhere('o.idWalletCreditor = :idWallet')
                ->setParameter('idWallet', $wallet);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /***
     * @param Wallet         $debtorWallet
     * @param array          $operationTypes
     * @param array|null     $operationSubTypes
     * @param \DateTime|null $start
     *
     * @return mixed
     */
    public function sumDebitOperationsByTypeSince(Wallet $debtorWallet, $operationTypes, $operationSubTypes = null, \DateTime $start = null)
    {
        $qb = $this->createQueryBuilder('o');
        $qb->select('SUM(o.amount)')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label IN (:operationTypes)')
            ->andWhere('o.idWalletDebtor = :idWallet')
            ->setParameter('operationTypes', $operationTypes, Connection::PARAM_STR_ARRAY)
            ->setParameter('idWallet', $debtorWallet->getId());

        if (null !== $operationSubTypes) {
            $qb->innerJoin(OperationSubType::class, 'ost', Join::WITH, 'o.idSubType = ost.id')
                ->andWhere('ost.label IN (:operationSubTypes)')
                ->setParameter('operationSubTypes', $operationSubTypes, Connection::PARAM_STR_ARRAY);
        }

        if (null !== $start) {
            $start->setTime(0, 0, 0);
            $qb->andWhere('o.added >= :start')->setParameter('start', $start);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Loans|int      $loan
     * @param Receptions|int $wireTransferIn
     *
     * @return array
     */
    public function getTotalRepaidAmountsByLoanAndWireTransferIn($loan, $wireTransferIn)
    {
        $queryBuilder = $this->createQueryBuilder('o');
        $queryBuilder->select('
            SUM(CASE 
                WHEN ot.label = :capital THEN IFNULL(o.amount, 0)
                WHEN ot.label = :capitalRegularization THEN IFNULL(-o.amount, 0)
                ELSE 0
            END) as capital,
            SUM(CASE 
                WHEN ot.label = :interest THEN IFNULL(o.amount, 0)
                WHEN ot.label = :interestRegularization THEN IFNULL(-o.amount, 0)
                ELSE 0
            END) as interest
        ')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->innerJoin(ProjectRepaymentTaskLog::class, 'prtl', Join::WITH, 'o.idRepaymentTaskLog = prtl.id')
            ->innerJoin(ProjectRepaymentTask::class, 'prt', Join::WITH, 'prtl.idTask = prt.id')
            ->where('o.idLoan = :loan')
            ->andWhere('ot.label in (:repaymentTypes)')
            ->andWhere('prt.idWireTransferIn = :wireTransferIn')
            ->setParameter('loan', $loan)
            ->setParameter('wireTransferIn', $wireTransferIn)
            ->setParameter('capital', OperationType::CAPITAL_REPAYMENT)
            ->setParameter('capitalRegularization', OperationType::CAPITAL_REPAYMENT_REGULARIZATION)
            ->setParameter('interest', OperationType::GROSS_INTEREST_REPAYMENT)
            ->setParameter('interestRegularization', OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION)
            ->setParameter('repaymentTypes', [
                OperationType::CAPITAL_REPAYMENT,
                OperationType::CAPITAL_REPAYMENT_REGULARIZATION,
                OperationType::GROSS_INTEREST_REPAYMENT,
                OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION
            ]);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param Wallet|integer $wallet
     *
     * @return array
     */
    public function getFeesPaymentOperations($wallet)
    {
        $queryBuilder = $this->createQueryBuilder('o');
        $queryBuilder->select('SUM(o.amount) AS amount, DATE(o.added) AS added, IDENTITY(o.idWireTransferIn) AS idWireTransferIn, IDENTITY(o.idProject) AS idProject')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('o.idWalletCreditor = :wallet')
            ->andWhere('ot.label IN (:feePayment)')
            ->groupBy('o.idWireTransferIn')
            ->orderBy('o.added', 'DESC')
            ->setParameter('wallet', $wallet)
            ->setParameter('feePayment', [
                OperationType::COLLECTION_COMMISSION_LENDER,
                OperationType::COLLECTION_COMMISSION_BORROWER,
                OperationType::COLLECTION_COMMISSION_UNILEND,
            ]);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Temporary method for TMA-2686. Can be deleted later
     *
     * @var bool $groupByTaskLog
     * @var int  $idRepaymentTaskLog
     *
     * @return array
     *
     */
    public function getRetenuALaSourceTaxForPerson(bool $groupByTaskLog = false, ?int $idRepaymentTaskLog = null): array
    {
        $queryBuilder = $this->createQueryBuilder('o');
        $queryBuilder->select('o.id AS id, IDENTITY(o.idLoan) AS idLoan, IDENTITY(o.idRepaymentTaskLog) AS idRepaymentTaskLog')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'w.id = o.idWalletDebtor')
            ->innerJoin(Clients::class, 'c', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'ot.id = o.idType')
            ->where('c.type in (:person)')
            ->andWhere('ot.label = :taxOperationType')
            ->andWhere('YEAR(o.added) = :year')
            ->orderBy('o.added')
            ->setParameter('person', [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])
            ->setParameter('taxOperationType', OperationType::TAX_FR_RETENUES_A_LA_SOURCE)
            ->setParameter('year', 2018);

        if ($groupByTaskLog) {
            $queryBuilder->groupBy('o.idRepaymentTaskLog');
        }

        if ($idRepaymentTaskLog) {
            $queryBuilder->andWhere('o.idRepaymentTaskLog = :idRepaymentTaskLog')
                ->setParameter('idRepaymentTaskLog', $idRepaymentTaskLog);
        }

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * @param string $siren
     *
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getLastYearReleasedFundsBySIREN(string $siren)
    {
        $query = '
            SELECT IFNULL(SUM(o.amount), 0)
            FROM operation o
              INNER JOIN wallet w ON w.id = o.id_wallet_creditor
              INNER JOIN companies c ON c.id_client_owner = w.id_client
              INNER JOIN operation_type ot ON o.id_type = ot.id
            WHERE o.added > DATE_SUB(NOW(), INTERVAL 1 YEAR)
              AND c.siren = :siren
              AND ot.label = :loan';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['siren' => $siren, 'loan' => OperationType::LENDER_LOAN])
            ->fetchColumn();
    }

    /**
     * @param Wallet   $wallet
     * @param Projects $project
     *
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getNetRepaidAmountByWalletAndProject(Wallet $wallet, Projects $project)
    {
        $positiveOperations = array_merge(OperationType::TAX_TYPES_FR_REGULARIZATION, [OperationType::CAPITAL_REPAYMENT, OperationType::GROSS_INTEREST_REPAYMENT]);
        $negativeOperations = array_merge(OperationType::TAX_TYPES_FR, [OperationType::CAPITAL_REPAYMENT_REGULARIZATION, OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION]);

        $query = '
            SELECT
              SUM(IF(ot.label IN (:positiveOperations), o.amount, -o.amount))
            FROM wallet_balance_history wbh
              INNER JOIN operation o ON wbh.id_operation = o.id
              INNER JOIN operation_type ot ON o.id_type = ot.id
            WHERE wbh.id_wallet = :idWallet
              AND o.id_project = :idProject
              AND ot.label IN (:allOperations)';

        $bind = [
            'positiveOperations' => $positiveOperations,
            'allOperations'      => array_merge($positiveOperations, $negativeOperations),
            'idWallet'           => $wallet->getId(),
            'idProject'          => $project->getIdProject()
        ];
        $types = [
            'positiveOperations' => Connection::PARAM_STR_ARRAY,
            'allOperations'      => Connection::PARAM_STR_ARRAY,
            'idWallet'           => \PDO::PARAM_INT,
            'idProject'          => \PDO::PARAM_INT
        ];

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, $bind, $types)
            ->fetchColumn();
    }
}
