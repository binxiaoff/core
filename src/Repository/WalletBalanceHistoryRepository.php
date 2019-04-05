<?php

namespace Unilend\Repository;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{Clients, Echeanciers, Operation, OperationSubType, OperationType, ProjectRepaymentTask, ProjectRepaymentTaskLog, Receptions, SepaRejectionReason, Wallet, WalletBalanceHistory,
    WalletType};
use Unilend\Service\LenderOperationsManager;
use Unilend\librairies\CacheKeys;

class WalletBalanceHistoryRepository extends EntityRepository
{
    /**
     * @param  Wallet|integer $wallet
     * @param \DateTime       $date
     *
     * @return null|WalletBalanceHistory
     */
    public function getBalanceOfTheDay($wallet, \DateTime $date)
    {
        if ($wallet instanceof Wallet) {
            $wallet = $wallet->getId();
        }

        $date->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('w');
        $qb->andWhere('w.idWallet = :wallet')
            ->andWhere('w.added <= :dateTime')
            ->setParameters(['wallet' => $wallet, 'dateTime' => $date])
            ->orderBy('w.added', 'DESC')
            ->addOrderBy('w.id', 'DESC')
            ->setMaxResults(1);
        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param Wallet    $wallet
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function getLenderOperationHistory(Wallet $wallet, \DateTime $start, \DateTime $end)
    {
        $start->setTime(00, 00, 00);
        $end->setTime(23, 59, 59);

        $query = '
            SELECT
                wbh.id AS id,
                wbh.available_balance,
                wbh.committed_balance,
                ROUND(
                    IF(
                        wbh.id_operation IS NOT NULL,
                        IF(
                            o.id_wallet_creditor = wbh.id_wallet,
                            o.amount,
                            IF(ot.label = "' . OperationType::LENDER_LOAN . '", o.amount, - o.amount)
                        ),
                        IF(wbh.id_autobid IS NULL,
                          IF(1 = (SELECT COUNT(*) FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_bid = wbh.id_bid), -b.amount / 100,
                             IF(EXISTS(SELECT * FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_bid = wbh.id_bid AND wbh.added < wbh_bid.added),
                                -b.amount / 100,
                                 IF(b.amount != ab.amount, b.amount / 100 - ab.amount / 100, b.amount / 100))
                          ),
                          IF(1 = (SELECT COUNT(*) FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_autobid = wbh.id_autobid AND wbh_bid.id_project = wbh.id_project),
                             -b.amount / 100,
                             IF(EXISTS(SELECT * FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_autobid = wbh.id_autobid AND wbh_bid.id_project = wbh.id_project AND wbh.added < wbh_bid.added),
                                 -b.amount / 100,
                                IF(b.amount != ab.amount, b.amount / 100 - ab.amount / 100, b.amount / 100))
                          )
                       )
                    ), 2) AS amount,
                IF(
                    ot.label IS NOT NULL,
                    ot.label,
                    IF(
                        wbh.id_bid IS NULL,
                        "' . LenderOperationsManager::OP_REFUSED_BID . '",
                        IF(
                            wbh.id_autobid IS NULL,
                            IF(
                                1 = (SELECT COUNT(*) FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_bid = wbh.id_bid),
                                "' . LenderOperationsManager::OP_BID . '",
                                IF(EXISTS(SELECT * FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_bid = wbh.id_bid AND wbh.added < wbh_bid.added), "' . LenderOperationsManager::OP_BID . '", "' . LenderOperationsManager::OP_REFUSED_BID . '")
                            ),
                            IF(
                                1 = (SELECT COUNT(*) FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_autobid = wbh.id_autobid AND wbh_bid.id_project = wbh.id_project),
                                "' . LenderOperationsManager::OP_AUTOBID . '",
                                IF(EXISTS(SELECT * FROM wallet_balance_history wbh_bid WHERE wbh_bid.id_autobid = wbh.id_autobid AND wbh_bid.id_project = wbh.id_project AND wbh.added < wbh_bid.added), "' . LenderOperationsManager::OP_AUTOBID . '" , "' . LenderOperationsManager::OP_REFUSED_AUTOBID . '")
                            )
                        )
                )) AS label,
                DATE(wbh.added) AS date,
                wbh.added AS operationDate,
                wbh.id_bid,
                wbh.id_autobid,
                IF(wbh.id_loan IS NOT NULL, wbh.id_loan, IF(o.id_loan IS NOT NULL, o.id_loan, IF(e.id_loan IS NOT NULL, e.id_loan, ""))) AS id_loan,
                o.id_repayment_schedule,
                IFNULL(o.id_repayment_task_log, wbh.id) AS order_by_id,
                id_repayment_task_log,
                p.id_project,
                p.title,
                ost.label AS sub_type_label
            FROM wallet_balance_history wbh
                INNER JOIN wallet w ON wbh.id_wallet = w.id
                LEFT JOIN operation o ON wbh.id_operation = o.id
                LEFT JOIN operation_type ot ON ot.id = o.id_type
                LEFT JOIN operation_sub_type ost ON o.id_sub_type = ost.id
                LEFT JOIN echeanciers e ON e.id_echeancier = o.id_repayment_schedule
                LEFT JOIN bids b ON wbh.id_bid = b.id_bid
                LEFT JOIN accepted_bids ab ON b.id_bid = ab.id_bid
                LEFT JOIN projects p ON IF(o.id_project IS NULL, wbh.id_project, o.id_project) = p.id_project
            WHERE wbh.id_wallet = :idWallet
                AND wbh.added BETWEEN :startDate AND :endDate
            GROUP BY id_loan, order_by_id
            ORDER BY wbh.id DESC';

        $qcProfile = new QueryCacheProfile(CacheKeys::LONG_TIME, md5(__METHOD__ . $wallet->getId()));
        $statement = $this->getEntityManager()->getConnection()->executeCacheQuery($query, [
            'idWallet'  => $wallet->getId(),
            'startDate' => $start->format('Y-m-d H:i:s'),
            'endDate'   => $end->format('Y-m-d H:i:s')
        ], [
            'idWallet'  => \PDO::PARAM_INT,
            'startDate' => \PDO::PARAM_STR,
            'endDate'   => \PDO::PARAM_STR
        ], $qcProfile);

        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param Wallet $wallet
     * @param int    $idWalletBalanceHistory
     *
     * @return null|WalletBalanceHistory
     */
    public function getPreviousLineForWallet(Wallet $wallet, $idWalletBalanceHistory)
    {
        $qb = $this->createQueryBuilder('wbh');
        $qb->where('wbh.id < :idWbh')
            ->andWhere('wbh.idWallet = :idWallet')
            ->orderBy('wbh.id', 'DESC')
            ->setMaxResults(1)
            ->setParameter('idWallet', $wallet)
            ->setParameter('idWbh', $idWalletBalanceHistory)
            ->setCacheable(true);

        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param int|Wallet  $idWallet
     * @param \DateTime   $startDate
     * @param \DateTime   $endDate
     * @param array       $idProjects
     * @param string|null $operationType
     *
     * @return array
     */
    public function getBorrowerWalletOperations($idWallet, \DateTime $startDate, \DateTime $endDate, array $idProjects = [], $operationType = null)
    {
        $qb = $this->getClientWalletOperationsQuery($idWallet, $startDate, $endDate);
        $qb->addSelect('
                CASE 
                    WHEN(o.idWalletDebtor = wbh.idWallet)  
                    THEN -SUM(o.amount) 
                    ELSE SUM(o.amount)
                END AS amount,
                e.ordre,
                IDENTITY(r.rejectionIsoCode) AS rejectionIsoCode,
                srr.label as rejectionReasonLabel,
                CASE WHEN ot.label in (:repaymentTypes) THEN prt.id WHEN ot.label = :loan THEN CONCAT(\'loan_\', IDENTITY(o.idProject)) ELSE o.id END AS HIDDEN forGroupBy
                '
        )
            ->leftJoin(Receptions::class, 'r', Join::WITH, 'o.idWireTransferIn = r.idReception')
            ->leftJoin(SepaRejectionReason::class, 'srr', Join::WITH, 'r.rejectionIsoCode = srr.isoCode')
            ->leftJoin(Echeanciers::class, 'e', Join::WITH, 'o.idRepaymentSchedule = e.idEcheancier')
            ->leftJoin(ProjectRepaymentTaskLog::class, 'prtl', Join::WITH, 'o.idRepaymentTaskLog = prtl.id')
            ->leftJoin(ProjectRepaymentTask::class, 'prt', Join::WITH, 'prt.id = prtl.idTask')
            ->addGroupBy('forGroupBy')
            ->setParameter('repaymentTypes', [
                OperationType::CAPITAL_REPAYMENT,
                OperationType::CAPITAL_REPAYMENT_REGULARIZATION,
                OperationType::GROSS_INTEREST_REPAYMENT,
                OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION
            ])
            ->setParameter('loan', OperationType::LENDER_LOAN);

        if (false === empty($idProjects)) {
            $qb->andWhere('o.idProject IN (:idProjects)')
                ->setParameter('idProjects', $idProjects, Connection::PARAM_INT_ARRAY);
        }
        if (null !== $operationType) {
            $qb->andWhere('ot.label = :operationType')
                ->setParameter('operationType', $operationType);
        }

        $query = $qb->getQuery();

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Unilend\Doctrine\ORM\UsePrimaryKeyForInnerJoinWalker');

        return $query->getResult();
    }

    /**
     * @param           $idWallet
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     *
     * @return array
     */
    public function getDebtCollectorWalletOperations($idWallet)
    {
        $queryBuilder = $this->getClientWalletOperationsQuery($idWallet);
        $queryBuilder->addSelect('
            CASE 
                WHEN(o.idWalletDebtor = wbh.idWallet)  
                THEN -SUM(o.amount) 
                ELSE SUM(o.amount)
            END AS amount,
            CASE WHEN ot.label in (:feePayment) THEN CONCAT(ot.label, IDENTITY(o.idWireTransferIn)) ELSE o.id END AS HIDDEN forGroupBy
            ')
            ->addGroupBy('forGroupBy')
            ->setParameter('feePayment', [
                OperationType::COLLECTION_COMMISSION_LENDER,
                OperationType::COLLECTION_COMMISSION_BORROWER,
                OperationType::COLLECTION_COMMISSION_UNILEND,
            ]);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param                $idWallet
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getClientWalletOperationsQuery($idWallet, ?\DateTime $startDate = null, ?\DateTime $endDate = null)
    {
        $queryBuilder = $this->createQueryBuilder('wbh')
            ->select('
                o.id,
                CASE 
                    WHEN o.idSubType IS NULL
                    THEN ot.label
                    ELSE ost.label
                END AS label, 
                IDENTITY(o.idProject) AS idProject, 
                DATE(o.added) AS date'
            )
            ->innerJoin(Operation::class, 'o', Join::WITH, 'o.id = wbh.idOperation')
            ->innerJoin(OperationType::class, 'ot', Join::WITH, 'o.idType = ot.id')
            ->leftJoin(OperationSubType::class, 'ost', Join::WITH, 'o.idSubType = ost.id')
            ->where('wbh.idWallet = :idWallet')
            ->setParameter('idWallet', $idWallet)
            ->orderBy('wbh.id', 'DESC');

        if ($startDate instanceof \DateTime) {
            $startDate->setTime(00, 00, 00);
            $queryBuilder->andWhere('o.added >= :startDate')
                ->setParameter('startDate', $startDate->format('Y-m-d H:i:s'));
        }

        if ($endDate instanceof \DateTime) {
            $endDate->setTime(23, 59, 59);
            $queryBuilder->andWhere('o.added <= :endDate')
                ->setParameter('endDate', $endDate->format('Y-m-d H:i:s'));
        }

        return $queryBuilder;
    }

    /**
     * @param \DateTime $date
     * @param array     $walletTypes
     *
     * @return float
     */
    public function sumBalanceForDailyState(\DateTime $date, array $walletTypes)
    {
        $date->setTime(23, 59, 59);

        $query = 'SELECT
                    IF(SUM(wbh_line.committed_balance) IS NOT NULL,(SUM(wbh_line.available_balance) + SUM(wbh_line.committed_balance)),SUM(wbh_line.available_balance)) AS balance
                  FROM wallet_balance_history wbh_line
                    INNER JOIN (SELECT MAX(wbh_max.id) AS id FROM wallet_balance_history wbh_max
                                  INNER JOIN wallet w ON wbh_max.id_wallet = w.id
                                  INNER JOIN wallet_type wt ON w.id_type = wt.id
                                WHERE wbh_max.added <= :end
                                  AND wt.label IN (:walletLabels)
                                GROUP BY wbh_max.id_wallet) wbh_max ON wbh_line.id = wbh_max.id';

        $result = $this->getEntityManager()->getConnection()
            ->executeQuery($query,
                ['end' => $date->format('Y-m-d H:i:s'), 'walletLabels' => $walletTypes],
                ['end' => \PDO::PARAM_STR, 'walletLabels' => Connection::PARAM_STR_ARRAY]
            )->fetchColumn(0);

        if ($result === null) {
            return '0.00';
        }

        return $result;
    }

    /**
     * @param \DateTime $month
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getMonthlyRepayments(\DateTime $month): array
    {
        $firstDayOfThisMonth = (clone $month)->modify('first day of this month')->setTime(0, 0);
        $lastDayOfThisMonth  = (clone $month)->modify('last day of this month')->setTime(23, 59, 59);

        $query = '
        SELECT
          c.id_client,
          CASE
            WHEN c.type IN (:person) THEN 1
            WHEN c.type IN (:legalEntity) THEN 2
            ELSE "inconnu"
          END AS type,
          (
            SELECT p.iso
            FROM lenders_imposition_history lih
              JOIN pays p ON p.id_pays = lih.id_pays
            WHERE lih.added <= o.added AND lih.id_lender = w.id
            ORDER BY lih.added DESC
            LIMIT 1
          ) AS resident_fiscal,
          CASE (IFNULL(
               (SELECT id_pays
                FROM lenders_imposition_history lih
                WHERE lih.id_lender = w.id AND lih.added <= o.added
                ORDER BY added DESC
                LIMIT 1)
               , 0) IN (0, 1) AND c.type IN (:person))
            WHEN TRUE THEN 0
            ELSE 1
          END AS taxed_at_source,
          CASE
            WHEN lte.year IS NULL THEN 0
            ELSE 1
          END AS exonere,
          (
            SELECT GROUP_CONCAT(lte.year SEPARATOR ", ")
            FROM lender_tax_exemption lte
            WHERE lte.id_lender = w.id
          ) AS annees_exoneration,
          o.id_project,
          o.id_loan,
          uc.label AS type_contract,
          e.ordre,
          REPLACE(ROUND(e.capital / 100, 2), \'.\', \',\') AS capital,
          REPLACE(ROUND(e.interets / 100, 2), \'.\', \',\') AS interets,
          CASE e.status
            WHEN :scheduleRepaid THEN \'complete\'
            WHEN :schedulePartiallyRepaid THEN \'partielle\'
            ELSE \'\'
          END AS status_echeance,
          e.date_echeance,
          e.date_echeance_emprunteur,
          IF(o.id_repayment_schedule IS NULL, \'hors échéance\', \'échéance\') AS type_remboursement,
          REPLACE(SUM(IF(ot.label = :capital, o.amount, IF(ot.label = :capitalRegularization, -o.amount, 0))), \'.\', \',\') AS capital_rembourse,
          REPLACE(SUM(IF(ot.label = :interest, o.amount, IF(ot.label = :interestRegularization, -o.amount, 0))), \'.\', \',\') AS interets_rembourse,
          o.added                                                                                     AS date_rembourse,
          e.date_echeance_emprunteur_reel,
          REPLACE(SUM(IF(ot.label = :prelevementsObligatoires, o.amount, IF(ot.label = :prelevementsObligatoiresRegularization, -o.amount, 0))), \'.\', \',\') AS prelevements_obligatoires,
          REPLACE(SUM(IF(ot.label = :retenuesSource, o.amount, IF(ot.label = :retenuesSourceRegularization, -o.amount, 0))), \'.\', \',\') AS retenues_source,
          REPLACE(SUM(IF(ot.label = :csg, o.amount, IF(ot.label = :csgRegularization, -o.amount, 0))), \'.\', \',\') AS csg,
          REPLACE(SUM(IF(ot.label = :prelevementsSociaux, o.amount, IF(ot.label = :prelevementsSociauxRegularization, -o.amount, 0))), \'.\', \',\') AS prelevements_sociaux,
          REPLACE(SUM(IF(ot.label = :contributionsAdditionnelles, o.amount, IF(ot.label = :contributionsAdditionnellesRegularization, -o.amount, 0))), \'.\', \',\') AS contributions_additionnelles,
          REPLACE(SUM(IF(ot.label = :prelevementsSolidarite, o.amount, IF(ot.label = :prelevementsSolidariteRegularization, -o.amount, 0))), \'.\', \',\') AS prelevements_de_solidarite,
          REPLACE(SUM(IF(ot.label = :crds, o.amount, IF(ot.label = :crdsRegularization, -o.amount, 0))), \'.\', \',\') AS crds
        FROM wallet_balance_history wbh
          INNER JOIN operation o ON wbh.id_operation = o.id
          INNER JOIN operation_type ot ON o.id_type = ot.id
          INNER JOIN loans l ON l.id_loan = o.id_loan
          INNER JOIN underlying_contract uc ON l.id_type_contract = uc.id_contract
          INNER JOIN wallet w ON w.id = wbh.id_wallet
          INNER JOIN wallet_type wt ON w.id_type = wt.id
          INNER JOIN clients c ON c.id_client = w.id_client
          LEFT JOIN echeanciers e ON e.id_echeancier = o.id_repayment_schedule
          LEFT JOIN lender_tax_exemption lte ON lte.id_lender = w.id AND lte.year = YEAR(wbh.added)
        WHERE
          wbh.added BETWEEN :startDate AND :endDate
          AND ot.label IN (:repaymentAndTax)
          AND (e.status_ra = :notEarlyRepayment OR e.status_ra IS NULL)
          AND wt.label = :lender
        GROUP BY o.id_wire_transfer_in, o.id_loan';

        return $this->getEntityManager()->getConnection()->executeQuery(
            $query,
            [
                'person'                                    => [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER],
                'legalEntity'                               => [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER],
                'startDate'                                 => $firstDayOfThisMonth->format('Y-m-d H:i:s'),
                'endDate'                                   => $lastDayOfThisMonth->format('Y-m-d H:i:s'),
                'capital'                                   => OperationType::CAPITAL_REPAYMENT,
                'capitalRegularization'                     => OperationType::CAPITAL_REPAYMENT_REGULARIZATION,
                'interest'                                  => OperationType::GROSS_INTEREST_REPAYMENT,
                'interestRegularization'                    => OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION,
                'prelevementsObligatoires'                  => OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES,
                'prelevementsObligatoiresRegularization'    => OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES_REGULARIZATION,
                'retenuesSource'                            => OperationType::TAX_FR_RETENUES_A_LA_SOURCE,
                'retenuesSourceRegularization'              => OperationType::TAX_FR_RETENUES_A_LA_SOURCE_REGULARIZATION,
                'csg'                                       => OperationType::TAX_FR_CSG,
                'csgRegularization'                         => OperationType::TAX_FR_CSG_REGULARIZATION,
                'prelevementsSociaux'                       => OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX,
                'prelevementsSociauxRegularization'         => OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX_REGULARIZATION,
                'contributionsAdditionnelles'               => OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES,
                'contributionsAdditionnellesRegularization' => OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES_REGULARIZATION,
                'prelevementsSolidarite'                    => OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE,
                'prelevementsSolidariteRegularization'      => OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE_REGULARIZATION,
                'crds'                                      => OperationType::TAX_FR_CRDS,
                'crdsRegularization'                        => OperationType::TAX_FR_CRDS_REGULARIZATION,
                'repaymentAndTax'                           => array_merge(
                    [
                        OperationType::CAPITAL_REPAYMENT,
                        OperationType::CAPITAL_REPAYMENT_REGULARIZATION,
                        OperationType::GROSS_INTEREST_REPAYMENT,
                        OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION
                    ],
                    OperationType::TAX_TYPES_FR,
                    OperationType::TAX_TYPES_FR_REGULARIZATION
                ),
                'notEarlyRepayment'                         => Echeanciers::IS_NOT_EARLY_REPAID,
                'lender'                                    => WalletType::LENDER,
                'scheduleRepaid' => Echeanciers::STATUS_REPAID,
                'schedulePartiallyRepaid' => Echeanciers::STATUS_PARTIALLY_REPAID,
            ],
            ['person' => Connection::PARAM_STR_ARRAY, 'legalEntity' => Connection::PARAM_STR_ARRAY, 'repaymentAndTax' => Connection::PARAM_STR_ARRAY]
        )->fetchAll(\PDO::FETCH_ASSOC);
    }
}
