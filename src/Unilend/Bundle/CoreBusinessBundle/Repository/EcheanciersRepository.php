<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\FrontBundle\Controller\LenderDashboardController;

class EcheanciersRepository extends EntityRepository
{
    public function getLostCapitalForLender($idLender)
    {
        $projectStatusCollectiveProceeding = [
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE,
            \projects_status::DEFAUT
        ];

        $qb = $this->createQueryBuilder('e');
        $qb->select('SUM(e.capital)')
            ->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH, 'e.idProject = p.idProject')
            ->where('e.idLender = :idLender')
            ->andWhere('e.status = ' . \echeanciers::STATUS_PENDING)
            ->andWhere('p.status IN (:projectStatus) OR (p.status = ' . \projects_status::RECOUVREMENT . ' AND DATEDIFF(NOW(), e.dateEcheance) > 180)')
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
     * @param int|null         $status
     * @param int|null         $paymentStatus
     * @param int|null         $earlyRepaymentStatus
     * @param int|null         $start
     * @param int|null         $limit
     *
     * @return Echeanciers[]
     */
    public function findByProject($project, $repaymentSequence = null, $client = null, $status = null, $paymentStatus = null, $earlyRepaymentStatus = null, $start = null, $limit = null)
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

        if (null !== $status) {
            $qb->andWhere('e.status = :status')
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
              IF(prelevements_obligatoires.amount IS NULL, 0, prelevements_obligatoires.amount),
              IF(retenues_source.amount IS NULL, 0, retenues_source.amount),
              IF(csg.amount IS NULL, 0, csg.amount),
              IF(prelevements_sociaux.amount IS NULL, 0, prelevements_sociaux.amount),
              IF(contributions_additionnelles.amount IS NULL, 0, contributions_additionnelles.amount),
              IF(prelevements_solidarite.amount IS NULL, 0, prelevements_solidarite.amount),
              IF(crds.amount IS NULL, 0, crds.amount),
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
              LEFT JOIN operation prelevements_obligatoires ON prelevements_obligatoires.id_repayment_schedule = e.id_echeancier AND prelevements_obligatoires.id_type = (SELECT id FROM operation_type WHERE label = \'' . OperationType::TAX_FR_STATUTORY_CONTRIBUTIONS . '\')
              LEFT JOIN operation retenues_source ON retenues_source.id_repayment_schedule = e.id_echeancier AND retenues_source.id_type = (SELECT id FROM operation_type WHERE label = \''. OperationType::TAX_FR_INCOME_TAX_DEDUCTED_AT_SOURCE .'\')
              LEFT JOIN operation csg ON csg.id_repayment_schedule = e.id_echeancier AND csg.id_type = (SELECT id FROM operation_type WHERE label = \'' . OperationType::TAX_FR_CSG . '\')
              LEFT JOIN operation prelevements_sociaux ON prelevements_sociaux.id_repayment_schedule = e.id_echeancier AND prelevements_sociaux.id_type = (SELECT id FROM operation_type WHERE label = \'' .  OperationType::TAX_FR_SOCIAL_DEDUCTIONS . '\')
              LEFT JOIN operation contributions_additionnelles ON contributions_additionnelles.id_repayment_schedule = e.id_echeancier AND contributions_additionnelles.id_type  = (SELECT id FROM operation_type WHERE label = \'' . OperationType::TAX_FR_ADDITIONAL_CONTRIBUTIONS . '\')
              LEFT JOIN operation prelevements_solidarite ON prelevements_solidarite.id_repayment_schedule = e.id_echeancier AND prelevements_solidarite.id_type  = (SELECT id FROM operation_type WHERE label = \'' . OperationType::TAX_FR_SOLIDARITY_DEDUCTIONS . '\')
              LEFT JOIN operation crds ON crds.id_repayment_schedule = e.id_echeancier AND crds.id_type  = (SELECT id FROM operation_type WHERE label = \'' . OperationType::TAX_FR_CRDS . '\')
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
}
