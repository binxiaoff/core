<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;

class ReceptionsRepository extends EntityRepository
{
    /**
     * @param \DateTime $date
     *
     * @return array
     */
    public function getByDate(\DateTime $date)
    {
        $from = new \DateTime($date->format("Y-m-d") . " 00:00:00");
        $to   = new \DateTime($date->format("Y-m-d") . " 23:59:59");

        $queryBuilder = $this->createQueryBuilder("r");
        $queryBuilder->andWhere('r.added BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get all assigned the direct debts or wire transfers to borrowers
     *
     * @return Users[]
     */
    public function getBorrowerAttributions()
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.idProject IS NOT NULL')
            ->orderBy('r.idReception', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get all assigned the direct debts or wire transfers to lenders
     *
     * @return Users[]
     */
    public function getLenderAttributions()
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.idClient IS NOT NULL')
            ->andWhere('r.idProject IS NULL')
            ->andWhere('r.type = :directDebit AND r.statusPrelevement = :directDebitSent OR r.type = :wireTransfer AND r.statusVirement = :wireTransferReceived')
            ->setParameter('directDebit', Receptions::TYPE_DIRECT_DEBIT)
            ->setParameter('directDebitSent', Receptions::DIRECT_DEBIT_STATUS_SENT)
            ->setParameter('wireTransfer', Receptions::TYPE_WIRE_TRANSFER)
            ->setParameter('wireTransferReceived', Receptions::WIRE_TRANSFER_STATUS_RECEIVED)
            ->orderBy('r.idReception', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Projects $project
     *
     * @return Receptions[]
     */
    public function getBorrowerAnticipatedRepaymentWireTransfer(Projects $project)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->where('r.idProject = :projectId')
            ->andWhere('r.statusBo IN (:assignmentType)')
            ->andWhere('r.typeRemb = :earlyRepayment')
            ->andWhere('r.type = :wireTransfer')
            ->andWhere('r.statusVirement = :wireTransferReceived')
            ->setParameter('projectId', $project)
            ->setParameter('assignmentType', [Receptions::STATUS_ASSIGNED_MANUAL, Receptions::STATUS_ASSIGNED_AUTO], Connection::PARAM_INT_ARRAY)
            ->setParameter('earlyRepayment', Receptions::REPAYMENT_TYPE_EARLY)
            ->setParameter('wireTransfer', Receptions::TYPE_WIRE_TRANSFER)
            ->setParameter('wireTransferReceived', Receptions::WIRE_TRANSFER_STATUS_RECEIVED);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function getRejectedDirectDebitIndicatorsBetweenDates(\DateTime $start, \DateTime $end)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->select('COUNT(r.idReception) AS number')
            ->addSelect('ROUND(SUM(r.montant) / 100, 2) AS amount')
            ->where('r.type = :directDebit')
            ->andWhere('r.statusPrelevement = :rejected')
            ->andWhere('r.added BETWEEN :start AND :end')
            ->setParameter('directDebit', Receptions::TYPE_DIRECT_DEBIT)
            ->setParameter('rejected', Receptions::DIRECT_DEBIT_STATUS_REJECTED)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        return $queryBuilder->getQuery()->getArrayResult()[0];
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return mixed
     */
    public function getBorrowerProvisionRegularizationIndicatorsBetweenDates(\DateTime $start, \DateTime $end)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->select('COUNT(r.idReception) AS number')
            ->addSelect('ROUND(SUM(r.montant) / 100, 2) AS amount')
            ->where('r.type != :directDebit')
            ->andWhere('r.typeRemb = :regularization')
            ->andWhere('r.added BETWEEN :start AND :end')
            ->setParameter('directDebit', Receptions::TYPE_DIRECT_DEBIT)
            ->setParameter('regularization', Receptions::REPAYMENT_TYPE_REGULARISATION)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        return $queryBuilder->getQuery()->getArrayResult()[0];
    }

    /**
     * @param Projects|int $project
     * @param \DateTime    $from
     *
     * @return mixed
     */
    public function findOriginalDirectDebitByRejectedOne($project, \DateTime $from)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->where('r.idProject = :project')
            ->andWhere('r.statusBo in (:affected)')
            ->andWhere('r.statusPrelevement = :sent')
            ->andWhere('r.type = :directDebit')
            ->andWhere('r.added >= :from')
            ->setParameter('project', $project)
            ->setParameter('affected', [Receptions::STATUS_ASSIGNED_AUTO, Receptions::STATUS_ASSIGNED_MANUAL])
            ->setParameter('sent', Receptions::DIRECT_DEBIT_STATUS_SENT)
            ->setParameter('directDebit', Receptions::TYPE_DIRECT_DEBIT)
            ->setParameter('from', $from)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}

