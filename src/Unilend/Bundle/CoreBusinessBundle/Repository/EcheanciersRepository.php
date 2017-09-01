<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Service\TaxManager;
use Unilend\Bundle\FrontBundle\Controller\LenderDashboardController;
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
        $projectStatusCollectiveProceeding = [
            ProjectsStatus::PROCEDURE_SAUVEGARDE,
            ProjectsStatus::REDRESSEMENT_JUDICIAIRE,
            ProjectsStatus::LIQUIDATION_JUDICIAIRE,
            ProjectsStatus::DEFAUT
        ];

        $qb = $this->createQueryBuilder('e');
        $qb->select('SUM(e.capital)')
            ->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH, 'e.idProject = p.idProject')
            ->where('e.idLender = :idLender')
            ->andWhere('e.status = ' . \echeanciers::STATUS_PENDING)
            ->andWhere('p.status IN (:projectStatus) OR (p.status = ' . ProjectsStatus::RECOUVREMENT . ' AND DATEDIFF(NOW(), e.dateEcheance) > 180)')
            ->setParameter('idLender', $idLender)
            ->setParameter('projectStatus', $projectStatusCollectiveProceeding, Connection::PARAM_INT_ARRAY);

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
        $qb->select('ROUND(SUM((e.capital + e.interets) / 100), 2) AS amount')
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
        $qb->innerJoin('UnilendCoreBusinessBundle:Loans', 'l', Join::WITH, 'e.idLoan = l.idLoan')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'w.id = l.idLender')
            ->innerJoin('UnilendCoreBusinessBundle:EcheanciersEmprunteur', 'ee', Join::WITH, 'ee.idProject = l.idProject AND ee.ordre = e.ordre')
            ->where('l.idProject = :project')
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
     * @param \DateTime $date
     *
     * @return array|null
     * @throws \Exception
     */
    public function getRepaymentScheduleIncludingTaxOnDate(\DateTime $date)
    {
        $query = '
            SELECT
              c.id_client,
              CASE c.type
                WHEN 1 THEN 1
                WHEN 3 THEN 1
                WHEN 2 THEN 2
                WHEN 4 THEN 2
                ELSE "inconnu"
              END AS type,
              (
                SELECT p.iso
                FROM lenders_imposition_history lih
                  JOIN pays_v2 p ON p.id_pays = lih.id_pays
                WHERE lih.added <= e.date_echeance_reel
                      AND lih.id_lender = e.id_lender
                ORDER BY lih.added DESC
                LIMIT 1
              ) AS iso_pays,
              /*if the lender is FR resident and it is a physical person then it is not taxed at source : taxed_at_source = 0*/
              CASE
                  (IFNULL(
                      (SELECT resident_etranger
                          FROM lenders_imposition_history lih
                          WHERE lih.id_lender = w.id AND lih.added <= e.date_echeance_reel
                          ORDER BY added DESC
                          LIMIT 1)
                      , 0) = 0 AND (1 = c.type OR 3 = c.type))
                WHEN TRUE
                  THEN 0
                  ELSE 1
                END AS taxed_at_source,
              CASE
                  WHEN lte.year IS NULL THEN 0
                  ELSE 1
              END AS exonere,
              (SELECT group_concat(lte.year SEPARATOR ", ")
               FROM lender_tax_exemption lte
               WHERE lte.id_lender = w.id) AS annees_exoneration,
              e.id_project,
              e.id_loan,
              l.id_type_contract,
              e.ordre,
              ROUND(e.montant / 100, 2),
              ROUND(e.capital_rembourse / 100, 2),
              ROUND(e.interets_rembourses / 100, 2),
              IFNULL((
               SELECT SUM(amount)
               FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
               WHERE ot.label = \'' . OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES . '\'  AND id_repayment_schedule = e.id_echeancier
              ), 0) - IFNULL((
                SELECT SUM(amount)
                FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
                WHERE ot.label = \'' . OperationType::TAX_FR_PRELEVEMENTS_OBLIGATOIRES_REGULARIZATION . '\' AND id_repayment_schedule = e.id_echeancier
              ), 0) AS prelevements_obligatoires,
              IFNULL((
               SELECT SUM(amount)
               FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
               WHERE ot.label = \'' . OperationType::TAX_FR_RETENUES_A_LA_SOURCE . '\'  AND id_repayment_schedule = e.id_echeancier
              ), 0) - IFNULL((
                SELECT SUM(amount)
                FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
                WHERE ot.label = \'' . OperationType::TAX_FR_RETENUES_A_LA_SOURCE_REGULARIZATION . '\' AND id_repayment_schedule = e.id_echeancier
              ), 0) AS retenues_source,
              IFNULL((
               SELECT SUM(amount)
               FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
               WHERE ot.label = \'' . OperationType::TAX_FR_CSG . '\'  AND id_repayment_schedule = e.id_echeancier
              ), 0) - IFNULL((
                SELECT SUM(amount)
                FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
                WHERE ot.label = \'' . OperationType::TAX_FR_CSG_REGULARIZATION . '\' AND id_repayment_schedule = e.id_echeancier
              ), 0) AS csg,
              IFNULL((
               SELECT SUM(amount)
               FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
               WHERE ot.label = \'' . OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX . '\'  AND id_repayment_schedule = e.id_echeancier
              ), 0) - IFNULL((
                SELECT SUM(amount)
                FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
                WHERE ot.label = \'' . OperationType::TAX_FR_PRELEVEMENTS_SOCIAUX_REGULARIZATION . '\' AND id_repayment_schedule = e.id_echeancier
              ), 0) AS prelevements_sociaux,
              IFNULL((
               SELECT SUM(amount)
               FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
               WHERE ot.label = \'' . OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES . '\'  AND id_repayment_schedule = e.id_echeancier
              ), 0) - IFNULL((
                SELECT SUM(amount)
                FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
                WHERE ot.label = \'' . OperationType::TAX_FR_CONTRIBUTIONS_ADDITIONNELLES_REGULARIZATION . '\' AND id_repayment_schedule = e.id_echeancier
              ), 0) AS contributions_additionnelles,
              IFNULL((
               SELECT SUM(amount)
               FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
               WHERE ot.label = \'' . OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE . '\'  AND id_repayment_schedule = e.id_echeancier
              ), 0) - IFNULL((
                SELECT SUM(amount)
                FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
                WHERE ot.label = \'' . OperationType::TAX_FR_PRELEVEMENTS_DE_SOLIDARITE_REGULARIZATION . '\' AND id_repayment_schedule = e.id_echeancier
              ), 0) AS prelevements_de_solidarite,
              IFNULL((
               SELECT SUM(amount)
               FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
               WHERE ot.label = \'' . OperationType::TAX_FR_CRDS . '\'  AND id_repayment_schedule = e.id_echeancier
              ), 0) - IFNULL((
                SELECT SUM(amount)
                FROM operation o INNER JOIN operation_type ot ON ot.id = o.id_type
                WHERE ot.label = \'' . OperationType::TAX_FR_CRDS_REGULARIZATION . '\' AND id_repayment_schedule = e.id_echeancier
              ), 0) AS crds,
              e.date_echeance,
              e.date_echeance_reel,
              e.status,
              e.date_echeance_emprunteur,
              e.date_echeance_emprunteur_reel
            FROM echeanciers e
              INNER JOIN loans l ON l.id_loan = e.id_loan
              INNER JOIN wallet w ON w.id = e.id_lender
              INNER JOIN clients c ON c.id_client = w.id_client
              LEFT JOIN lender_tax_exemption lte ON lte.id_lender = e.id_lender AND lte.year = YEAR(e.date_echeance_reel)
            WHERE e.date_echeance_reel BETWEEN :startDate AND :endDate
                AND e.status IN (' . Echeanciers::STATUS_REPAID . ', ' . Echeanciers::STATUS_PARTIALLY_REPAID . ')
                AND e.status_ra = 0
            ORDER BY e.date_echeance ASC';

        return $this->getEntityManager()->getConnection()->executeQuery(
            $query,
            ['startDate' => $date->format('Y-m-d 00:00:00'), 'endDate' => $date->format('Y-m-d 23:59:59')],
            ['startDate' => \PDO::PARAM_STR, 'endDate' => \PDO::PARAM_STR]
        )->fetchAll(\PDO::FETCH_ASSOC);
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
                WHERE e.id_lender = :lender
                    AND e.status = 0
                    AND e.date_echeance >= NOW()
                    AND IF(
                        (p.status IN (' . implode(',',
                [ProjectsStatus::PROCEDURE_SAUVEGARDE, ProjectsStatus::REDRESSEMENT_JUDICIAIRE, ProjectsStatus::LIQUIDATION_JUDICIAIRE, ProjectsStatus::DEFAUT]) . ')
                        OR (p.status >= ' . ProjectsStatus::PROBLEME . '
                        AND DATEDIFF(NOW(), (
                        SELECT psh2.added
                        FROM projects_status_history psh2
                        INNER JOIN projects_status ps2 ON psh2.id_project_status = ps2.id_project_status
                        WHERE ps2.status = ' . ProjectsStatus::PROBLEME . '
                        AND psh2.id_project = e.id_project
                        ORDER BY psh2.added DESC, psh2.id_project_status_history DESC
                        LIMIT 1
                    )) > 180)), TRUE, FALSE) = FALSE
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
                'tax_type_legal_entity_lender' => TaxManager::TAX_TYPE_LEGAL_ENTITY_LENDER
            ],
            [
                'repaymentTypes'               => Connection::PARAM_INT_ARRAY,
                'frenchTax'                    => Connection::PARAM_INT_ARRAY,
                'frenchTaxRegularisation'      => Connection::PARAM_INT_ARRAY,
                'allOperationTypes'            => Connection::PARAM_INT_ARRAY,
                'tax_type_exempted_lender'     => Connection::PARAM_INT_ARRAY,
                'tax_type_taxable_lender'      => Connection::PARAM_INT_ARRAY,
                'tax_type_foreigner_lender'    => Connection::PARAM_INT_ARRAY,
                'tax_type_legal_entity_lender' => Connection::PARAM_INT_ARRAY
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
                  WHERE psh2.added <= :end
                    AND ps2.status IN (:status)
                    AND e.status = :pending
                    AND e.date_echeance <= :end';

        return $this->getEntityManager()->getConnection()->executeQuery($query, [
            'end'             => $end->format('Y-m-d H:i:s'),
            'repaymentStatus' => ProjectsStatus::REMBOURSEMENT,
            'status'          => [ProjectsStatus::REMBOURSEMENT, ProjectsStatus::PROBLEME, ProjectsStatus::PROBLEME_J_X],
            'pending'         => Echeanciers::STATUS_PENDING
        ], [
            'end'             => \PDO::PARAM_STR,
            'repaymentStatus' => \PDO::PARAM_INT,
            'status'          => Connection::PARAM_INT_ARRAY,
            'pending'         => \PDO::PARAM_INT
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
                    SET capital_rembourse = capital, status = :paid, date_echeance_reel = NOW(), status_email_remb = :sent, updated = NOW()
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
     * @param \DateTime    $date
     * @param Projects|int $project
     *
     * @return null|Echeanciers
     */
    public function findNextPendingScheduleAfter(\DateTime $date, $project)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->where('e.idProject = :project')
            ->andWhere('e.status = :pending')
            ->andWhere('e.dateEcheance >= :date')
            ->setParameter('project', $project)
            ->setParameter('date', $date)
            ->setParameter('pending', Echeanciers::STATUS_PENDING)
            ->orderBy('e.ordre', 'ASC')
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Projects|int $project
     * @param int          $sequence
     *
     * @return float
     */
    public function getUnpaidAmount($project, $sequence)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->select('SUM(e.capital + e.interets - e.capitalRembourse - e.interetsRembourses)')
            ->where('e.idProject = :project')
            ->andWhere('e.ordre = :sequence')
            ->setParameter('project', $project)
            ->setParameter('sequence', $sequence);

        return round(bcdiv($queryBuilder->getQuery()->getSingleScalarResult(), 100, 4), 2);
    }
}
