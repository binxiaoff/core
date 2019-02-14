<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;

class PrelevementsRepository extends EntityRepository
{

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function sumDirectDebitByDay(\DateTime $start, \DateTime $end)
    {
        $start->setTime(00, 00, 00);
        $end->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('p');
        $qb->select('ROUND(SUM(p.montant) / 100, 2) AS amount')
            ->addSelect('DATE(p.addedXml) AS date')
            ->where('p.addedXml BETWEEN :start AND :end')
            ->andWhere('p.status > :statusPending')
            ->groupBy('date')
            ->orderBy('DATE(p.addedXml)', 'ASC')
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'))
            ->setParameter('statusPending', Prelevements::STATUS_PENDING);
        $result = $qb->getQuery()->getResult();
        $sums   = [];
        foreach ($result as $row) {
            $sums[$row['date']] = $row['amount'];
        }

        return $sums;
    }

    /**
     * @param int $year
     *
     * @return array
     */
    public function sumDirectDebitByMonth($year)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('ROUND(SUM(p.montant) / 100, 2) AS amount')
            ->addSelect('MONTH(p.addedXml) AS month')
            ->where('YEAR(p.addedXml) = :year')
            ->andWhere('p.status > :statusPending')
            ->groupBy('month')
            ->orderBy('MONTH(p.addedXml)', 'ASC')
            ->setParameter('year', $year)
            ->setParameter('statusPending', Prelevements::STATUS_PENDING);
        $result = $qb->getQuery()->getResult();
        $sums   = [];
        foreach ($result as $row) {
            $sums[$row['month']] = $row['amount'];
        }

        return $sums;
    }

    /**
     * @param $project
     *
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function terminatePendingDirectDebits($project)
    {
        if ($project instanceof Projects) {
            $project = $project->getIdProject();
        }
        $update = 'UPDATE prelevements SET status = :finished, updated = NOW() WHERE id_project = :project AND status = :pending';

        return $this->getEntityManager()->getConnection()->executeUpdate($update, ['project' => $project, 'finished' => Prelevements::STATUS_TERMINATED, 'pending' => Prelevements::STATUS_PENDING]);
    }

    /**
     * @param int|Projects $projectId
     *
     * @return array
     */
    public function findUpcomingDirectDebitsByProject($projectId)
    {
        if ($projectId instanceof Projects) {
            $projectId = $projectId->getIdProject();
        }
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.idProject = :projectId')
            ->setParameter('projectId', $projectId)
            ->andWhere('p.status = :pending')
            ->setParameter('pending', Prelevements::STATUS_PENDING)
            ->andWhere('p.typePrelevement = 1')
            ->andWhere('p.dateExecutionDemandePrelevement > NOW()');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $daysInterval
     *
     * @return Prelevements[]
     */
    public function getUpcomingDirectDebits(int $daysInterval): array
    {
        $queryBuilder = $this->createQueryBuilder('pre');
        $queryBuilder
            ->innerJoin('UnilendCoreBusinessBundle:Projects', 'pro', Join::WITH, 'pre.idProject = pro.idProject')
            ->innerJoin('UnilendCoreBusinessBundle:EcheanciersEmprunteur', 'ee', Join::WITH, 'pre.idProject = ee.idProject AND ee.ordre = pre.numPrelevement')
            ->where('pro.status = :repayment')
            ->andWhere('ee.statusEmprunteur = :pending')
            ->andWhere('pre.type = :borrower')
            ->andWhere('DATE(ee.dateEcheanceEmprunteur) = :date')
            ->setParameters([
                'repayment' => ProjectsStatus::STATUS_REPAYMENT,
                'pending'   => EcheanciersEmprunteur::STATUS_PENDING,
                'borrower'  => Prelevements::CLIENT_TYPE_BORROWER,
                'date'      => (new \DateTime('+' . $daysInterval . ' days'))->format('Y-m-d')
            ]);

        return $queryBuilder->getQuery()->getResult();
    }
}
