<?php

namespace Unilend\Repository;

use Doctrine\DBAL\{Cache\QueryCacheProfile, Connection};
use Doctrine\ORM\{EntityRepository, NoResultException, Query\Expr\Join};
use Unilend\Entity\{Clients, Companies, CompanyStatus, Echeanciers, EcheanciersEmprunteur, Loans, OperationType, Projects, ProjectsStatus, UnilendStats, Wallet};
use Unilend\Service\TaxManager;
use Unilend\Controller\LenderDashboardController;
use Unilend\librairies\CacheKeys;

class EcheanciersRepository extends EntityRepository
{
    /**
     * @param int $idLender
     *
     * @return mixed
     */
    public function getLostCapitalForLender($idLender)
    {
        $companyStatus = [
            CompanyStatus::STATUS_PRECAUTIONARY_PROCESS,
            CompanyStatus::STATUS_RECEIVERSHIP,
            CompanyStatus::STATUS_COMPULSORY_LIQUIDATION
        ];

        $qb = $this->createQueryBuilder('e');
        $qb->select('SUM(e.capital)')
            ->innerJoin(Projects::class, 'p', Join::WITH, 'e.idProject = p.idProject')
            ->innerJoin(Companies::class, 'c', Join::WITH, 'c.idCompany = p.idCompany')
            ->innerJoin(CompanyStatus::class, 'cs', Join::WITH, 'cs.id = c.idStatus')
            ->where('e.idLender = :idLender')
            ->andWhere('e.status = ' . Echeanciers::STATUS_PENDING)
            ->andWhere('cs.label IN (:companyStatus) OR (p.status = ' . ProjectsStatus::STATUS_LOSS . ' AND DATEDIFF(NOW(), e.dateEcheance) > ' . UnilendStats::DAYS_AFTER_LAST_PROBLEM_STATUS_FOR_STATISTIC_LOSS . ')')
            ->setParameter('idLender', $idLender)
            ->setParameter('companyStatus', $companyStatus, Connection::PARAM_STR_ARRAY);

        $amount = $qb->getQuery()->getSingleScalarResult();

        return $amount;
    }

    /**
     * @param int    $idLender
     * @param string $timeFrame
     *
     * @return string
     * @throws \Exception
     */
    public function getMaxRepaymentAmountForLender($idLender, $timeFrame)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('ROUND(SUM((e.capital + e.interets)) / 100, 2) AS amount')
            ->where('e.idLender = :idLender')
            ->orderBy('amount', 'DESC')
            ->groupBy('timeFrame')
            ->setMaxResults(1)
            ->setParameter('idLender', $idLender);

        switch ($timeFrame) {
            case LenderDashboardController::REPAYMENT_TIME_FRAME_MONTH :
                $qb->addSelect('LPAD(e.dateEcheance, 7, \' \' ) AS timeFrame');
                break;
            case LenderDashboardController::REPAYMENT_TIME_FRAME_QUARTER:
                $qb->addSelect('QUARTER(e.dateEcheance) AS timeFrame');
                break;
            case LenderDashboardController::REPAYMENT_TIME_FRAME_YEAR:
                $qb->addSelect('YEAR(e.dateEcheance) AS timeFrame');
                break;
            default:
                throw new \Exception('Time frame is not supported, see LenderDashboardController for possibilities');
                break;
        }

        $result = $qb->getQuery()->getResult();
        if (empty($result)) {
            return 0;
        }

        return $result[0]['amount'];
    }

    /**
     * @param Projects|int     $project
     * @param int|null         $repaymentSequence
     * @param Clients|int|null $client
     * @param int|array|null   $status
     * @param int|null         $paymentStatus
     * @param int|null         $earlyRepaymentStatus
     * @param int|null         $start
     * @param int|null         $limit
     *
     * @return Echeanciers[]
     */
    public function findByProject($project, $repaymentSequence = null, $client = null, $status = [], $paymentStatus = null, $earlyRepaymentStatus = null, $start = null, $limit = null)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->innerJoin(Loans::class, 'l', Join::WITH, 'e.idLoan = l.idLoan')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'w.id = l.wallet')
            ->innerJoin(EcheanciersEmprunteur::class, 'ee', Join::WITH, 'ee.idProject = l.project AND ee.ordre = e.ordre')
            ->where('l.project = :project')
            ->setParameter('project', $project);

        if (null !== $repaymentSequence) {
            $qb->andwhere('e.ordre = :repaymentSequence')
                ->setParameter('repaymentSequence', $repaymentSequence);
        }

        if (null !== $client) {
            $qb->andWhere('w.idClient = :client')
                ->setParameter('client', $client);
        }

        if (0 < count($status)) {
            if (false === is_array($status)) {
                $status = [$status];
            }
            $qb->andWhere('e.status in (:status)')
                ->setParameter('status', $status);
        }

        if (null !== $paymentStatus) {
            $qb->andWhere('ee.statusEmprunteur = :paymentStatus')
                ->setParameter('paymentStatus', $paymentStatus);
        }

        if (null !== $earlyRepaymentStatus) {
            $qb->andWhere('e.statusRa = :earlyRepaymentStatus')
                ->setParameter('earlyRepaymentStatus', $earlyRepaymentStatus);
        }

        if (null !== $start) {
            $qb->setFirstResult($start);
        }

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Loans|int $loan
     *
     * @return float
     */
    public function getEarlyRepaidCapitalByLoan($loan)
    {
        $queryBuilder = $this->createQueryBuilder('e');

        $queryBuilder->select('ROUND(SUM(e.capitalRembourse) / 100, 2)')
            ->where('e.idLoan = :loan')
            ->andWhere('e.statusRa = :earlyRepaid')
            ->setParameter('loan', $loan)
            ->setParameter('earlyRepaid', Echeanciers::IS_EARLY_REPAID);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns capital, interests and tax sum amounts grouped by month, quarter and year for a lender
     * takes into account regular past payments at their real date
     * recovery payments including commission
     * future payments of healthy (according to stats definition) only projects
     *
     * @param Wallet|int $lender
     *
     * @return array
     */
    public function getLenderRepaymentsDetails($lender)
    {
        if ($lender instanceof Wallet) {
            $lender = $lender->getId();
        }

        $query = '
            SELECT
                t.month                                                               AS month,
                t.quarter                                                             AS quarter,
                t.year                                                                AS year,
                ROUND(SUM(t.capital), 2)                                              AS capital,
                ROUND(SUM(t.grossInterests), 2)                                       AS grossInterests,
                ROUND(SUM(t.grossInterests - t.repaidTaxes - t.upcomingTaxes), 2)     AS netInterests,
                ROUND(SUM(t.repaidTaxes + t.upcomingTaxes), 2)                        AS taxes
            FROM (
                SELECT
                 LEFT(o.added, 7) AS month,
                 QUARTER(o.added) AS quarter,
                 YEAR(o.added) AS year,
                 SUM(IF(ot.label = \'' . OperationType::CAPITAL_REPAYMENT_REGULARIZATION . '\', -amount, IF(ot.label = \'' . OperationType::CAPITAL_REPAYMENT . '\', amount, 0))) AS capital,
                 SUM(IF(ot.label = \'' . OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION . '\', -amount, IF(ot.label= \'' . OperationType::GROSS_INTEREST_REPAYMENT . '\', amount, 0))) AS grossInterests,
                 SUM(IF(ot.label IN (:frenchTaxRegularisation), -amount, IF(ot.label IN (:frenchTax), amount, 0))) AS repaidTaxes,
                 0 upcomingTaxes
               FROM operation o USE INDEX (idx_id_wallet_creditor_type, idx_id_wallet_debitor_type)
                 INNER JOIN operation_type ot ON ot.id = o.id_type
               WHERE
                 ot.label IN (:allOperationTypes)
                 AND (o.id_wallet_creditor = :lender
                      OR o.id_wallet_debtor = :lender)
               GROUP BY LEFT(o.added, 7)

                UNION ALL

                SELECT
                    LEFT(e.date_echeance, 7)        AS month,
                    QUARTER(e.date_echeance)        AS quarter,
                    YEAR(e.date_echeance)           AS year,
                    ROUND(SUM(e.capital) / 100, 2)  AS capital,
                    ROUND(SUM(e.interets) / 100, 2) AS grossInterests,
                    0                               AS repaidTaxes,
                    CASE c.type
                        -- Natural person
                        WHEN ' . Clients::TYPE_PERSON . ' OR ' . Clients::TYPE_PERSON_FOREIGNER . ' THEN
                            CASE lih.resident_etranger
                                -- FR fiscal resident
                                WHEN 0 THEN 
                                    IF (
                                        lte.id_lender IS NULL,
                                        SUM(ROUND((e.interets - e.interets_rembourses) * (SELECT SUM(tt.rate / 100) FROM tax_type tt WHERE tt.id_tax_type IN (:tax_type_taxable_lender)) / 100, 2)),
                                        SUM(ROUND((e.interets - e.interets_rembourses) * (SELECT SUM(tt.rate / 100) FROM tax_type tt WHERE tt.id_tax_type IN (:tax_type_exempted_lender)) / 100, 2))
                                    )
                                -- Foreigner fiscal resident
                                WHEN 1 THEN
                                    SUM(ROUND((e.interets - e.interets_rembourses) * (SELECT tt.rate / 100 FROM tax_type tt WHERE tt.id_tax_type IN (:tax_type_foreigner_lender)) / 100, 2))
                            END
                        -- Legal entity
                        WHEN ' . Clients::TYPE_LEGAL_ENTITY . ' OR ' . Clients::TYPE_LEGAL_ENTITY_FOREIGNER . ' THEN
                            SUM(ROUND((e.interets - e.interets_rembourses) * (SELECT tt.rate / 100 FROM tax_type tt WHERE tt.id_tax_type IN (:tax_type_legal_entity_lender)) / 100, 2))
                    END AS upcomingTaxes
                FROM echeanciers e
                INNER JOIN wallet w ON e.id_lender = w.id
                LEFT JOIN clients c ON w.id_client = c.id_client
                LEFT JOIN lender_tax_exemption lte ON lte.id_lender = e.id_lender AND lte.year = YEAR(e.date_echeance)
                LEFT JOIN lenders_imposition_history lih ON lih.id_lenders_imposition_history = (SELECT MAX(id_lenders_imposition_history) FROM lenders_imposition_history WHERE id_lender = e.id_lender)
                LEFT JOIN projects p ON e.id_project = p.id_project
                LEFT JOIN companies com ON p.id_company = com.id_company
                LEFT JOIN company_status cs ON cs.id = com.id_status
                WHERE e.id_lender = :lender
                    AND e.status = ' . Echeanciers::STATUS_PENDING . '
                    AND e.date_echeance >= NOW()
                    AND IF(
                        (cs.label IN (:companyStatus)
                        OR p.status = ' . ProjectsStatus::STATUS_LOSS . '
                        OR (p.status = ' . ProjectsStatus::STATUS_LOSS . '
                            AND DATEDIFF(NOW(), (
                                SELECT psh2.added
                                FROM projects_status_history psh2
                                INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                                WHERE ps2.status = ' . ProjectsStatus::STATUS_LOSS . '
                                    AND psh2.id_project = e.id_project
                                ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                                LIMIT 1
                            )
                        ) > ' . UnilendStats::DAYS_AFTER_LAST_PROBLEM_STATUS_FOR_STATISTIC_LOSS . ')), TRUE, FALSE) = FALSE
                GROUP BY month
            ) AS t
            GROUP BY t.month';

        $frenchTax               = OperationType::TAX_TYPES_FR;
        $frenchTaxRegularisation = OperationType::TAX_TYPES_FR_REGULARIZATION;
        $repaymentTypes          = [
            OperationType::CAPITAL_REPAYMENT,
            OperationType::CAPITAL_REPAYMENT_REGULARIZATION,
            OperationType::GROSS_INTEREST_REPAYMENT,
            OperationType::GROSS_INTEREST_REPAYMENT_REGULARIZATION
        ];
        $allOperationTypes       = array_merge($frenchTax, $frenchTaxRegularisation, $repaymentTypes);

        $oQCProfile    = new QueryCacheProfile(CacheKeys::DAY, md5(__METHOD__));
        $statement     = $this->getEntityManager()->getConnection()->executeQuery(
            $query,
            [
                'lender'                       => $lender,
                'frenchTax'                    => $frenchTax,
                'frenchTaxRegularisation'      => $frenchTaxRegularisation,
                'repaymentTypes'               => $repaymentTypes,
                'allOperationTypes'            => $allOperationTypes,
                'tax_type_exempted_lender'     => TaxManager::TAX_TYPE_EXEMPTED_LENDER,
                'tax_type_taxable_lender'      => TaxManager::TAX_TYPE_TAXABLE_LENDER,
                'tax_type_foreigner_lender'    => TaxManager::TAX_TYPE_FOREIGNER_LENDER,
                'tax_type_legal_entity_lender' => TaxManager::TAX_TYPE_LEGAL_ENTITY_LENDER,
                'companyStatus'                => [CompanyStatus::STATUS_PRECAUTIONARY_PROCESS, CompanyStatus::STATUS_RECEIVERSHIP, CompanyStatus::STATUS_COMPULSORY_LIQUIDATION]
            ],
            [
                'repaymentTypes'               => Connection::PARAM_INT_ARRAY,
                'frenchTax'                    => Connection::PARAM_INT_ARRAY,
                'frenchTaxRegularisation'      => Connection::PARAM_INT_ARRAY,
                'allOperationTypes'            => Connection::PARAM_INT_ARRAY,
                'tax_type_exempted_lender'     => Connection::PARAM_INT_ARRAY,
                'tax_type_taxable_lender'      => Connection::PARAM_INT_ARRAY,
                'tax_type_foreigner_lender'    => Connection::PARAM_INT_ARRAY,
                'tax_type_legal_entity_lender' => Connection::PARAM_INT_ARRAY,
                'companyStatus'                => Connection::PARAM_STR_ARRAY
            ],
            $oQCProfile
        );
        $repaymentData = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return $repaymentData;
    }

    /**
     * @param \DateTime $end
     *
     * @return array
     */
    public function getLateRepaymentIndicators(\DateTime $end)
    {
        $end->setTime(23, 59, 59);

        $query = 'SELECT
                        COUNT(DISTINCT e.id_project) AS projectCount,
                        ROUND(SUM(e.montant) / 100, 2) AS lateAmount
                  FROM (
                       SELECT MAX(id_project_status_history) AS first_status_history
                       FROM projects_status_history psh
                       GROUP BY id_project) AS t
                    INNER JOIN projects_status_history psh2 ON t.first_status_history = psh2.id_project_status_history
                    INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                    INNER JOIN echeanciers e ON e.id_project = psh2.id_project
                    INNER JOIN projects p ON p.id_project = psh2.id_project
                    INNER JOIN companies c ON c.id_company = p.id_company
                    INNER JOIN company_status cs ON cs.id = c.id_status
                    LEFT JOIN debt_collection_mission dcm ON p.id_project = dcm.id_project
                  WHERE psh2.added <= :endDate
                    AND ps2.status IN (:status)
                    AND cs.label = :inBonis
                    AND (dcm.id_project IS NULL OR dcm.added > :endDate OR (dcm.archived IS NOT NULL AND dcm.archived < :endDate))
                    AND e.status = :pending
                    AND e.date_echeance <= :endDate';

        return $this->getEntityManager()->getConnection()->executeQuery($query, [
            'endDate' => $end->format('Y-m-d H:i:s'),
            'status'  => [ProjectsStatus::STATUS_REPAYMENT, ProjectsStatus::STATUS_LOSS],
            'inBonis' => CompanyStatus::STATUS_IN_BONIS,
            'pending' => Echeanciers::STATUS_PENDING
        ], [
            'endDate' => \PDO::PARAM_STR,
            'status'  => Connection::PARAM_INT_ARRAY,
            'inBonis' => \PDO::PARAM_STR,
            'pending' => \PDO::PARAM_INT
        ])->fetchAll(\PDO::FETCH_ASSOC)[0];
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return mixed
     */
    public function findRepaidRepaymentsBetweenDates(\DateTime $start, \DateTime $end)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->where('e.status = :paid')
            ->andWhere('e.dateEcheanceReel BETWEEN :start AND :end')
            ->groupBy('e.idProject, e.ordre')
            ->setParameter('paid', Echeanciers::STATUS_REPAID)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return mixed
     */
    public function getSumRepaidRepaymentsBetweenDates(\DateTime $start, \DateTime $end)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->select('SUM(e.montant / 100)')
            ->where('e.status = :paid')
            ->andWhere('e.dateEcheanceReel BETWEEN :start AND :end')
            ->setParameter('paid', Echeanciers::STATUS_REPAID)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Loans $loan
     *
     * @return int
     */
    public function earlyRepayAllPendingSchedules(Loans $loan)
    {
        $updateRepaymentSchedule = 'UPDATE echeanciers
                    SET capital_rembourse = capital, status = :paid, date_echeance_reel = NOW(), updated = NOW()
                    WHERE id_loan = :loan AND status = :pending';

        $resultRepaymentSchedule = $this->getEntityManager()->getConnection()->executeUpdate(
            $updateRepaymentSchedule,
            [
                'loan'    => $loan->getIdLoan(),
                'sent'    => Echeanciers::STATUS_REPAYMENT_EMAIL_SENT,
                'paid'    => Echeanciers::STATUS_REPAID,
                'pending' => Echeanciers::STATUS_PENDING,
            ]
        );

        return $resultRepaymentSchedule;
    }

    /**
     * @param \DateTimeInterface $date
     * @param Projects|int       $project
     *
     * @return null|Echeanciers
     */
    public function findNextPendingScheduleAfter(\DateTimeInterface $date, $project)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->where('e.idProject = :project')
            ->andWhere('e.status = :pending')
            ->andWhere('date(e.dateEcheance) > :date')
            ->setParameter('project', $project)
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('pending', Echeanciers::STATUS_PENDING)
            ->orderBy('e.ordre', 'ASC')
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Projects|int $project
     * @param int          $sequence
     *
     * @return array
     */
    public function getNotRepaidAmountByProjectAndSequence($project, $sequence)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->select('ROUND(SUM(e.capital - e.capitalRembourse) / 100, 2) as capital, ROUND(SUM(e.interets - e.interetsRembourses) / 100, 2) as interest')
            ->where('e.idProject = :project')
            ->andWhere('e.ordre = :sequence')
            ->setParameter('project', $project)
            ->setParameter('sequence', $sequence);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param Projects|int $project
     * @param int          $sequence
     *
     * @return float
     */
    public function getNotRepaidCapitalByProjectAndSequence($project, $sequence)
    {
        $amount = $this->getNotRepaidAmountByProjectAndSequence($project, $sequence);

        return $amount['capital'];
    }

    /**
     * @param Projects|int $project
     * @param int          $sequence
     *
     * @return float
     */
    public function getNotRepaidInterestByProjectAndSequence($project, $sequence)
    {
        $amount = $this->getNotRepaidAmountByProjectAndSequence($project, $sequence);

        return $amount['interest'];
    }

    /**
     * @param Loans|int|array $loans
     *
     * @return float
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getRemainingCapitalByLoan($loans): float
    {
        if (false === is_array($loans)) {
            $loans = array($loans);
        }
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->select('ROUND(SUM(e.capital  - e.capitalRembourse) / 100, 2)')
            ->where('e.idLoan in (:loan)')
            ->setParameter('loan', $loans);

        return (float) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Loans|int      $loan
     * @param \DateTime|null $date
     *
     * @return array
     */
    public function getOverdueAmountsByLoan($loan, \DateTime $date = null)
    {
        if ($date === null) {
            $date = new \DateTime();
        }

        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->select('IFNULL(ROUND(SUM(e.capital  - e.capitalRembourse) / 100, 2), 0) AS capital, IFNULL(ROUND(SUM(e.interets  - e.interetsRembourses) / 100, 2), 0) AS interest')
            ->innerJoin(Loans::class, 'l', Join::WITH, 'e.idLoan = l.idLoan')
            ->innerJoin(EcheanciersEmprunteur::class, 'ee', Join::WITH, 'ee.idProject = l.project AND ee.ordre = e.ordre')
            ->where('e.idLoan = :loan')
            ->setParameter('loan', $loan)
            ->andWhere('ee.dateEcheanceEmprunteur < :today')
            ->setParameter('today', $date->format('Y-m-d 00:00:00'));

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param Loans|int      $loan
     * @param \DateTime|null $date
     *
     * @return float
     */
    public function getOverdueInterestByLoan($loan, \DateTime $date = null)
    {
        $amount = $this->getOverdueAmountsByLoan($loan, $date);

        return $amount['interest'];
    }

    /**
     * @param Loans|int      $loan
     * @param \DateTime|null $date
     *
     * @return float
     */
    public function getOverdueCapitalByLoan($loan, \DateTime $date = null)
    {
        $amount = $this->getOverdueAmountsByLoan($loan, $date);

        return $amount['capital'];
    }

    /**
     * @param Loans|int      $loan
     * @param \DateTime|null $date
     *
     * @return float
     */
    public function getTotalOverdueAmountByLoan($loan, \DateTime $date = null)
    {
        $amount = $this->getOverdueAmountsByLoan($loan, $date);

        return round(bcadd($amount['capital'], $amount['interest'], 4), 2);
    }

    /**
     * @param Projects|int $project
     *
     * @return array
     */
    public function getRemainingAmountsByLoanAndSequence($project)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->select('IDENTITY(e.idLoan) as idLoan, e.ordre, ROUND(SUM(e.capital  - e.capitalRembourse) / 100, 2) AS capital, ROUND(SUM(e.interets  - e.interetsRembourses) / 100, 2) AS interest')
            ->innerJoin(Loans::class, 'l', Join::WITH, 'e.idLoan = l.idLoan')
            ->where('l.project = :project')
            ->setParameter('project', $project)
            ->groupBy('e.idLoan')
            ->addGroupBy('e.ordre');

        $remainingAmounts                  = $queryBuilder->getQuery()->getArrayResult();
        $remainingAmountsByLoanAndSequence = [];

        foreach ($remainingAmounts as $remainingAmount) {
            $remainingAmountsByLoanAndSequence[$remainingAmount['idLoan']][$remainingAmount['ordre']] = $remainingAmount;
        }

        return $remainingAmountsByLoanAndSequence;
    }

    /**
     * @param $project
     *
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getOverdueRepaymentCountByProject($project)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->select('count(e)')
            ->where('e.idProject = :project')
            ->andWhere('e.status in (:unfinished)')
            ->andWhere('DATE(e.dateEcheance) < :today')
            ->setParameter('project', $project)
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->setParameter('unfinished', [Echeanciers::STATUS_PENDING, Echeanciers::STATUS_PARTIALLY_REPAID])
            ->groupBy('e.idProject, e.idLender')
            ->setMaxResults(1);

        try {
            return (int) $queryBuilder->getQuery()->getSingleScalarResult();
        } catch (NoResultException $exception) {
            return 0;
        }
    }

    /**
     * @param Projects|int $project
     *
     * @return Echeanciers
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findFirstOverdueScheduleByProject($project): Echeanciers
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder
            ->where('e.idProject = :idProject')
            ->andWhere('DATE(e.dateEcheance) <= CURRENT_DATE()')
            ->andWhere('e.status IN (:unfinished)')
            ->setParameter('idProject', $project)
            ->setParameter('unfinished', [Echeanciers::STATUS_PENDING, Echeanciers::STATUS_PARTIALLY_REPAID])
            ->groupBy('e.ordre')
            ->orderBy('e.ordre', 'ASC')
            ->setMaxResults(1)
            ->setFirstResult(0);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @return Echeanciers[]
     */
    public function findScheduledToday(): array
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder
            ->where('e.dateEcheance BETWEEN :startDate AND :endDate')
            ->andWhere('e.status = :pendingStatus')
            ->setParameter('startDate', new \DateTime('today midnight'))
            ->setParameter('endDate', new \DateTime('today 23:59:59'))
            ->setParameter('pendingStatus', Echeanciers::STATUS_PENDING)
            ->groupBy('e.idProject, e.ordre');

        return $queryBuilder->getQuery()->getResult();
    }


    /**
     * @param Wallet|int   $wallet
     * @param Projects|int $project
     * @param int          $order
     *
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getSumCapitalAndInterestByLenderAndProjectAndOrder($wallet, $project, int $order): int
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder
            ->select('SUM(e.capital + e.interets)')
            ->where('e.ordre = :order')
            ->andWhere('e.idLender = :wallet')
            ->andWhere('e.idProject = :project')
            ->setParameter('order', $order)
            ->setParameter('wallet', $wallet)
            ->setParameter('project', $project);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
