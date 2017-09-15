<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
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
     * @param int $projectId
     *
     * @return mixed
     */
    public function getUnpaidPaymentScheduleCount($projectId)
    {
        $queryBuilder = $this->createQueryBuilder('ee')
            ->select('COUNT(ee.idEcheancierEmprunteur)')
            ->where('ee.idProject = :projectId')
            ->setParameter('projectId', $projectId)
            ->andWhere('ee.statusEmprunteur = :pending')
            ->setParameter('pending', EcheanciersEmprunteur::STATUS_PENDING)
            ->andWhere('ee.dateEcheanceEmprunteur <= NOW()');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $projectId
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
            ->orderBy('ee.dateEcheanceEmprunteur', 'ASC');

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
