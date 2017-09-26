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
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTaskLog;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Doctrine\DBAL\Connection;
use Unilend\librairies\CacheKeys;

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

        $queryProjects = '
                        SELECT DISTINCT (p.id_project) FROM projects p
                        INNER JOIN projects_status_history psh ON p.id_project = psh.id_project
                        INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                        WHERE ps.status = ' . ProjectsStatus::REMBOURSEMENT . '
                        AND psh.added <= :end
                        AND p.status != ' . ProjectsStatus::DEFAUT;

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
    public function sumRegularizedNetInterestRepaymentsNotInEeaExceptFrance(Wallet $wallet, $year)
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
                                  o.id_wallet_debtor,
                                  o.id,
                                  (SELECT lih.id_pays
                                   FROM lenders_imposition_history lih
                                   WHERE id_lender = o.id_wallet_debtor AND DATE(lih.added) <= DATE(o.added)
                                   ORDER BY lih.added DESC
                                   LIMIT 1) AS id_pays
                                FROM operation o
                                  INNER JOIN operation_type ot ON o.id_type = ot.id
                                WHERE ot.label = "' . OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION . '" AND o.id_wallet_debtor = :idWallet) AS tlih ON o_interest.id = tlih.id
                    WHERE o_interest.id_type = (SELECT id FROM operation_type WHERE label = "' . OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION . '")
                    AND LEFT(o_interest.added, 4) = :year
                         AND o_interest.id_wallet_debtor = :idWallet';

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
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot_r', Join::WITH, 'o_r.idType = ot_r.id')
            ->where('ot_r.label IN (:taxRegularizationTypes)')
            ->andWhere('o_r.idRepaymentSchedule = :idRepaymentSchedule');
        $regularization = $qbRegularization->getDQL();

        $qb = $this->createQueryBuilder('o');
        $qb->select('IFNULL(SUM(o.amount), 0) as amount')
            ->addSelect('(' . $regularization . ') as regularized_amount')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
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
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot_r', Join::WITH, 'o_r.idType = ot_r.id')
            ->where('ot_r.label IN (:repaymentRegularizationTypes)')
            ->andWhere('o_r.idRepaymentSchedule = :idRepaymentSchedule');
        $regularization = $qbRegularization->getDQL();

        $qb = $this->createQueryBuilder('o');
        $qb->select('IFNULL(SUM(o.amount), 0) as amount')
            ->addSelect('(' . $regularization . ') as regularized_amount')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
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
                  CASE IFNULL((SELECT resident_etranger FROM lenders_imposition_history lih WHERE lih.id_lender = w.id AND lih.added <= e.date_echeance_reel ORDER BY added DESC LIMIT 1), 0)
                    WHEN 0 THEN "fr"
                    ELSE "ww"
                  END AS fiscal_residence,
                  CASE lte.id_lender
                    WHEN e.id_lender THEN "non_taxable"
                    ELSE "taxable"
                  END AS exemption_status,
                  SUM(o_interest.amount) AS interests
                FROM operation o_interest USE INDEX (idx_operation_added)
                  INNER JOIN operation_type ot_interest ON o_interest.id_type = ot_interest.id AND ot_interest.label = "' . $interestOperationType . '"
                  INNER JOIN wallet w ON o_interest.' . $walletField . ' = w.id
                  INNER JOIN clients c ON w.id_client = c.id_client
                  INNER JOIN echeanciers e ON o_interest.id_repayment_schedule = e.id_echeancier
                  INNER JOIN loans l ON l.id_loan = o_interest.id_loan
                  LEFT JOIN lender_tax_exemption lte ON lte.id_lender = w.id AND lte.year = YEAR(o_interest.added)
                WHERE o_interest.added BETWEEN :start AND :end
                GROUP BY l.id_type_contract, client_type, fiscal_residence,  exemption_status';
        return $this->getEntityManager()->getConnection()
            ->executeQuery($query, ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')])
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string    $taxOperationType
     * @param \DateTime $start
     * @param \DateTime $end
     * @param bool      $groupByContract
     * @param bool      $regularization
     *
     * @return array
     */
    public function getTaxForFiscalState($taxOperationType, \DateTime $start, \DateTime $end, $groupByContract = false, $regularization = false)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        if ($regularization) {
            $taxOperationType = $taxOperationType . '_REGULARIZATION';
        }

        $contractLabelColumn = $groupByContract ? ', uc.label as contract_label' : '';
        $groupBy             = $groupByContract ? 'GROUP BY l.id_type_contract' : '';

        $query = 'SELECT SUM(o_tax.amount) AS tax ' . $contractLabelColumn . '
                  FROM operation o_tax USE INDEX (idx_operation_added)
                    INNER JOIN operation_type ot_tax ON ot_tax.id = o_tax.id_type
                    INNER JOIN loans l ON l.id_loan = o_tax.id_loan
                    INNER JOIN underlying_contract uc ON uc.id_contract = l.id_type_contract
                  WHERE o_tax.added BETWEEN :start AND :end
                    AND ot_tax.label = \'' . $taxOperationType . '\'' . $groupBy;

        return $this->getEntityManager()->getConnection()
            ->executeQuery($query, ['start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s')])
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
     * @param Projects|integer $project
     * @param Clients[]        $clients
     *
     * @return float
     */
    public function getTotalGrossDebtCollectionRepayment($project, array $clients)
    {
        $qbRegularization = $this->createQueryBuilder('o_r');
        $qbRegularization->select('IFNULL(SUM(o_r.amount), 0)')
            ->innerJoin('UnilendCoreBusinessBundle:OperationSubType', 'ost_r', Join::WITH, 'o_r.idSubType = ost_r.id')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w_r', Join::WITH, 'w_r.id = o_r.idWalletDebtor')
            ->where('ost_r.label IN (:regularizationTypes)')
            ->andWhere('w.idClient IN (:clients)')
            ->andWhere('o.idProject = :project');
        $regularization = $qbRegularization->getDQL();

        $qb = $this->createQueryBuilder('o');
        $qb->select('IFNULL(SUM(o.amount), 0) as amount')
            ->addSelect('(' . $regularization . ') as regularized_amount')
            ->innerJoin('UnilendCoreBusinessBundle:OperationSubType', 'ost', Join::WITH, 'o.idSubType = ost.id')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'w.id = o.idWalletCreditor')
            ->where('ost.label = :operationSubType')
            ->andWhere('w.idClient IN (:clients)')
            ->andWhere('o.idProject = :project')
            ->setParameter('operationSubType', OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION)
            ->setParameter('regularizationTypes', OperationSubType::CAPITAL_REPAYMENT_DEBT_COLLECTION_REGULARIZATION)
            ->setParameter('clients', $clients)
            ->setParameter('project', $project);

        $result = $qb->getQuery()->getArrayResult();

        return round(bcsub($result[0]['amount'], $result[0]['regularized_amount'], 4), 2);
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
                     ELSE ot.label END AS movement
                FROM operation o USE INDEX (idx_operation_added)
                INNER JOIN operation_type ot ON o.id_type = ot.id
                LEFT JOIN operation_sub_type ost ON o.id_sub_type = ost.id';
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param array     $operationTypes
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
     * @param array     $operationTypes
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
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
            ->where('ot.label = :lenderProvision')
            ->andWhere('o.added BETWEEN :start AND :end')
            ->setParameter('lenderProvision', OperationType::LENDER_PROVISION)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        if ($groupByProvision) {
            $queryBuilder->addSelect('CASE WHEN (o.idBackpayline IS NOT NULL) THEN \'creditCard\' ELSE \'wireTransferIn\' END AS provisionType')
                ->groupBy('provisionType');
        }

        if ($onlineLenders) {
            $queryBuilder->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'o.idWalletCreditor = w.id')
                ->innerJoin('UnilendCoreBusinessBundle:Clients', 'c', Join::WITH, 'c.idClient = w.idClient')
                ->andWhere('c.status = :online')
                ->setParameter('online', Clients::STATUS_ONLINE);
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
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
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
            SELECT IFNULL(SUM(o_loan.amount), 0) - (
              SELECT IFNULL(SUM(o_repayment.amount), 0)
              FROM operation o_repayment
              INNER JOIN operation_type ot ON ot.id = o_repayment.id_type
              WHERE o_repayment.added <= :end
              AND ot.label = "' . OperationType::CAPITAL_REPAYMENT . '"
              AND o_repayment.id_project IN (:projects)
            ) - (
              SELECT IFNULL(SUM(o_repayment_regul.amount), 0)
              FROM operation o_repayment_regul
              INNER JOIN operation_type ot ON ot.id = o_repayment_regul.id_type
              WHERE o_repayment_regul.added <= :end
              AND ot.label = "' . OperationType::CAPITAL_REPAYMENT_REGULARIZATION . '"
              AND o_repayment_regul.id_project IN (:projects)
            )
            FROM operation o_loan
            INNER JOIN operation_type ot ON ot.id = o_loan.id_type
            WHERE o_loan.added <= :end
            AND ot.label = "' . OperationType::LENDER_LOAN . '"
            AND o_loan.id_project  IN (:projects)';

        $statement = $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['end' => $end->format('Y-m-d H:i:s'), 'projects' => $projects], ['end' => \PDO::PARAM_STR, 'projects' => Connection::PARAM_INT_ARRAY]);

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
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
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
            ->innerJoin('UnilendCoreBusinessBundle:OperationSubType', 'ost', Join::WITH, 'ost.id = o.idSubType')
            ->innerJoin('UnilendCoreBusinessBundle:Sponsorship', 'ss', Join::WITH, 'ss.id = o.idSponsorship')
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

        if (null !== $start) {
            $start->setTime(0, 0, 0);
            $qb->andWhere('o.added >= :start')->setParameter('start', $start);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
