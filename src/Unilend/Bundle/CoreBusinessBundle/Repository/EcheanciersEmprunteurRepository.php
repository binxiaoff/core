<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
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
}
