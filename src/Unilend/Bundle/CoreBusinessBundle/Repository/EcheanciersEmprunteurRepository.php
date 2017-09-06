<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
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
     * @param Projects|int $project
     *
     * @return EcheanciersEmprunteur[]
     */
    public function findUnFinishedSchedules($project)
    {
        $queryBuilder = $this->createQueryBuilder('ee');
        $queryBuilder->where('ee.idProject = :project')
            ->andWhere('ee.statusEmprunteur in (:unfinished)')
            ->orderBy('ee.ordre', 'ASC')
            ->setParameter('project', $project)
            ->setParameter('unfinished', [EcheanciersEmprunteur::STATUS_PENDING, EcheanciersEmprunteur::STATUS_PARTIALLY_PAID]);

        return $queryBuilder->getQuery()->getResult();
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
     * @param int $limit
     *
     * @return EcheanciersEmprunteur[]
     */
    public function findPaymentSchedulesToInvoice($limit)
    {
        $queryBuilder = $this->createQueryBuilder('ee');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH, 'ee.idProject = p.idProject')
            ->leftJoin('UnilendCoreBusinessBundle:Factures', 'f', Join::WITH, 'ee.idProject = f.idProject AND f.ordre = ee.ordre')
            ->where('DATE(ee.dateEcheanceEmprunteur) <= :today')
            ->andWhere('p.status in (:status)')
            ->andWhere('f.idFacture IS NULL')
            ->setParameter('today', (new \DateTime())->format('Y-m-d'))
            ->setParameter('status', [ProjectsStatus::REMBOURSEMENT, ProjectsStatus::PROBLEME, ProjectsStatus::PROBLEME_J_X])
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }
}
