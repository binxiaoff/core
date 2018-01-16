<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;

class EcheanciersEmprunteurRepository extends EntityRepository
{
    /**
     * @param Receptions $reception
     *
     * @return bool
     */
    public function earlyPayAllPendingSchedules(Receptions $reception)
    {
        $paidDate  = $reception->getAdded()->format('Y-m-d H:i:s');
        $projectId = $reception->getIdProject()->getIdProject();

        $updatePaymentSchedule = 'UPDATE echeanciers_emprunteur
                    SET status_emprunteur = :paid, status_ra = :earlyPaid, date_echeance_emprunteur_reel = :paidDate, updated = NOW()
                    WHERE id_project = :project AND status_emprunteur = :pending';

        $resultPaymentSchedule = $this->getEntityManager()->getConnection()->executeUpdate(
            $updatePaymentSchedule,
            [
                'project'   => $projectId,
                'paid'      => EcheanciersEmprunteur::STATUS_PAID,
                'earlyPaid' => EcheanciersEmprunteur::STATUS_EARLY_REPAYMENT_DONE,
                'paidDate'  => $paidDate,
                'pending'   => EcheanciersEmprunteur::STATUS_PENDING,
            ]
        );

        $updateRepaymentSchedule = 'UPDATE echeanciers
                    SET status_emprunteur = :paid, status_ra = :earlyPaid, date_echeance_emprunteur_reel = :paidDate, updated = NOW()
                    WHERE id_project = :project AND status_emprunteur = :pending';

        $resultRepaymentSchedule = $this->getEntityManager()->getConnection()->executeUpdate(
            $updateRepaymentSchedule,
            [
                'project'   => $projectId,
                'paid'      => EcheanciersEmprunteur::STATUS_PAID,
                'earlyPaid' => Echeanciers::IS_EARLY_REPAID,
                'paidDate'  => $paidDate,
                'pending'   => EcheanciersEmprunteur::STATUS_PENDING,
            ]
        );

        return $resultPaymentSchedule && $resultRepaymentSchedule;
    }

    /**
     * @param int|Projects $project
     * @param int          $sequence
     *
     * @return string
     */
    public function getRemainingCapitalFrom($project, $sequence)
    {
        $queryBuilder = $this->createQueryBuilder('ee');
        $queryBuilder->select('ROUND(SUM(ee.capital - ee.paidCapital) / 100, 2)')
            ->where('ee.idProject = :project')
            ->andWhere('ee.ordre >= :sequence')
            ->setParameter('project', $project)
            ->setParameter('sequence', $sequence);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Projects|int $project
     *
     * @return int
     */
    public function getOverdueScheduleCount($project)
    {
        $queryBuilder = $this->createQueryBuilder('ee');
        $queryBuilder->select('count(ee)')
            ->where('ee.idProject = :project')
            ->andWhere('ee.statusEmprunteur in (:unfinished)')
            ->andWhere('DATE(ee.dateEcheanceEmprunteur) < :today')
            ->setParameter('project', $project)
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->setParameter('unfinished', [EcheanciersEmprunteur::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PARTIALLY_PAID]);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $limit
     *
     * @return EcheanciersEmprunteur[]
     */
    public function findPaymentSchedulesToInvoice($limit)
    {
        $queryBuilder = $this->createQueryBuilder('ee');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH, 'ee.idProject = p.idProject')
            ->innerJoin('UnilendCoreBusinessBundle:Companies', 'c', Join::WITH, 'c.idCompany = p.idCompany')
            ->innerJoin('UnilendCoreBusinessBundle:CompanyStatus', 'cs', Join::WITH, 'cs.id = c.idStatus')
            ->leftJoin('UnilendCoreBusinessBundle:Factures', 'f', Join::WITH, 'ee.idProject = f.idProject AND f.ordre = ee.ordre')
            ->where('DATE(ee.dateEcheanceEmprunteur) <= :today')
            ->andWhere('p.status in (:status)')
            ->andWhere('p.closeOutNettingDate IS NULL OR p.closeOutNettingDate = :emptyDate')
            ->andWhere('cs.label = :inBonis')
            ->andWhere('f.idFacture IS NULL')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->setParameter('status', [ProjectsStatus::REMBOURSEMENT, ProjectsStatus::PROBLEME])
            ->setParameter('inBonis', CompanyStatus::STATUS_IN_BONIS)
            ->setParameter('emptyDate', '0000-00-00')
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Projects|int   $project
     * @param \DateTime|null $date
     *
     * @return mixed
     */
    public function getTotalOverdueAmounts($project, $date = null)
    {
        if (null === $date) {
            $date = new \DateTime();
        }
        $queryBuilder = $this->createQueryBuilder('ee');
        $queryBuilder->select(
            'ROUND(SUM(ee.capital - ee.paidCapital)/100, 2) as capital,
            ROUND(SUM(ee.interets - ee.paidInterest)/100, 2) as interest,
            ROUND(SUM(ee.commission + ee.tva - ee.paidCommissionVatIncl)/100, 2) as commission
            '
        )
            ->where('ee.idProject = :project')
            ->andWhere('ee.dateEcheanceEmprunteur < :today')
            ->setParameter('project', $project)
            ->setParameter('today', $date->format('Y-m-d 00:00:00'));

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param Projects|int $project
     *
     * @return array
     */
    public function getRemainingAmountsByProject($project)
    {
        $queryBuilder = $this->createQueryBuilder('ee');
        $queryBuilder->select(
            'ROUND(SUM(ee.capital - ee.paidCapital)/100, 2) as capital,
            ROUND(SUM(ee.interets - ee.paidInterest)/100, 2) as interest,
            ROUND(SUM(ee.commission + ee.tva - ee.paidCommissionVatIncl)/100, 2) as commission
            '
        )
            ->where('ee.idProject = :projectId')
            ->setParameter('projectId', $project);

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param bool           $groupFirstYears
     * @param \DateTime|null $date
     *
     * @return array
     */
    public function getTotalInterestToBePaidByCohortUntil($groupFirstYears = true, \DateTime $date = null)
    {
        if ($groupFirstYears) {
            $cohortSelect = 'CASE LEFT(projects_status_history.added, 4)
                                WHEN 2013 THEN "2013-2014"
                                WHEN 2014 THEN "2013-2014"
                                ELSE LEFT(projects_status_history.added, 4)
                            END';
        } else {
            $cohortSelect = 'LEFT(projects_status_history.added, 4)';
        }

        $bind = ['repayment' => ProjectsStatus::REMBOURSEMENT];

        $query = 'SELECT SUM(echeanciers_emprunteur.interets)/100 AS amount,
                  (
                    SELECT ' . $cohortSelect . ' AS date_range
                    FROM projects_status_history
                      INNER JOIN projects_status ON projects_status_history.id_project_status = projects_status.id_project_status
                    WHERE  projects_status.status = :repayment
                           AND echeanciers_emprunteur.id_project = projects_status_history.id_project
                    ORDER BY projects_status_history.added ASC, id_project_status_history ASC LIMIT 1
                  ) AS cohort
                FROM echeanciers_emprunteur
                  INNER JOIN projects ON echeanciers_emprunteur.id_project = projects.id_project AND projects.status >= :repayment';

        if (null !== $date) {
            $date->setTime(23, 59, 59);
            $bind  = array_merge($bind, ['end' => $date->format('Y-m-d H:i:s')]);
            $query .= 'WHERE
                   (
                        SELECT added
                        FROM projects_status_history psh
                          INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                        WHERE ps.status = :repayment
                          AND psh.id_project = projects.id_project
                        ORDER BY added ASC
                        LIMIT 1
                   ) <= :end';
        }

        $query .= ' GROUP BY cohort';

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, $bind);
        $result    = $statement->fetchAll();
        $statement->closeCursor();

        return $result;
    }

    /**
     * @param int|Projects $projectId
     *
     * @return null|EcheanciersEmprunteur
     */
    public function getNextPaymentSchedule($projectId)
    {
        $queryBuilder = $this->createQueryBuilder('ee')
            ->where('ee.idProject = :projectId')
            ->setParameter('projectId', $projectId)
            ->andWhere('DATE(ee.dateEcheanceEmprunteur) > :now')
            ->setParameter('now', (new \DateTime())->format('Y-m-d'))
            ->orderBy('ee.dateEcheanceEmprunteur', 'ASC')
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Projects|int $project
     *
     * @return float
     */
    public function getRemainingCapitalByProject($project)
    {
        $remaining = $this->getRemainingAmountsByProject($project);

        return $remaining['capital'];
    }
}
