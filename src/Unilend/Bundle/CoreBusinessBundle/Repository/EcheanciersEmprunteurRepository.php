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
        $queryBuilder->select('ROUND(SUM(ee.capital) / 100, 2)')
            ->where('ee.idProject = :project')
            ->andWhere('ee.ordre >= :sequence')
            ->andWhere('ee.statusEmprunteur = :pending')
            ->setParameter('project', $project)
            ->setParameter('pending', EcheanciersEmprunteur::STATUS_PENDING)
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
            ->andWhere('DATE(ee.dateEcheanceEmprunteur) <= :today')
            ->setParameter('project', $project)
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->setParameter('unfinished', [EcheanciersEmprunteur::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PARTIALLY_PAID]);

        return $queryBuilder->getQuery()->getSingleScalarResult();
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
            ->andWhere('p.closeOutNettingDate IS NULL')
            ->andWhere('cs.label = :inBonis')
            ->andWhere('f.idFacture IS NULL')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->setParameter('status', [ProjectsStatus::REMBOURSEMENT, ProjectsStatus::PROBLEME])
            ->setParameter('inBonis', CompanyStatus::STATUS_IN_BONIS)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

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
     * @param int|Projects $project
     * @param \DateTime    $endDate
     *
     * @return array
     */
    public function getPendingAmountAndPaymentsCountOnProjectAtDate($project, \DateTime $endDate)
    {
        $queryBuilder = $this->createQueryBuilder('ee')
            ->select('ROUND(SUM(ee.capital - ee.paidCapital + ee.interets - ee.paidInterest + ee.commission + ee.tva - ee.paidCommissionVatIncl) / 100, 2) AS amount,
            SUM(ROUND((ee.capital - ee.paidCapital + ee.interets - ee.paidInterest + ee.commission - ee.paidCommissionVatIncl) / (ee.capital + ee.interets + ee.commission), 1)) AS paymentsCount')
            ->where('ee.idProject = :projectId')
            ->setParameter('projectId', $project)
            ->andWhere('ee.dateEcheanceEmprunteur <= :endDate')
            ->setParameter('endDate', $endDate->format('Y-m-d 23:59:59'))
            ->groupBy('ee.idProject');

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param int|Projects $project
     * @param \DateTime    $startDate
     *
     * @return array
     */
    public function getPendingCapitalAndPaymentsCountOnProjectFromDate($project, \DateTime $startDate)
    {
        $queryBuilder = $this->createQueryBuilder('ee')
            ->select('ROUND(SUM(ee.capital - ee.paidCapital) / 100, 2) AS amount, SUM(ROUND((ee.capital - ee.paidCapital) / ee.capital, 1)) AS paymentsCount')
            ->where('ee.idProject = :projectId')
            ->setParameter('projectId', $project)
            ->andWhere('ee.dateEcheanceEmprunteur > :startDate')
            ->setParameter('startDate', $startDate->format('Y-m-d 00:00:00'))
            ->groupBy('ee.idProject');

        return $queryBuilder->getQuery()->getSingleResult();
    }

    /**
     * @param Projects|int $project
     *
     * @return int
     */
    public function getRemainingCapitalByProject($project)
    {
        $queryBuilder = $this->createQueryBuilder('ee');
        $queryBuilder->select('ROUND(SUM(ee.capital  - ee.paidCapital) / 100, 2)')
            ->where('ee.idProject = :projectId')
            ->setParameter('projectId', $project);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int|Projects $project
     *
     * @return mixed
     */
    public function getTotalAmountToRepayOnProject($project)
    {
        $queryBuilder = $this->createQueryBuilder('ee')
            ->select('ROUND(SUM(ee.capital + ee.interets + ee.commission + ee.tva) / 100, 2) AS totalAmountToRepay')
            ->where('ee.idProject = :project')
            ->setParameter('project', $project);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
