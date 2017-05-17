<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Unilend\Bridge\Doctrine\DBAL\Connection;
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
            ->andWhere('r.type = :directDebit AND r.statusPrelevement = :directDebitSent OR r.type = :wireTransfer AND r.statusVirement = :wireTransferReceived')
            ->setParameter('directDebit', Receptions::TYPE_DIRECT_DEBIT)
            ->setParameter('directDebitSent', Receptions::DIRECT_DEBIT_STATUS_SENT)
            ->setParameter('wireTransfer', Receptions::TYPE_WIRE_TRANSFER)
            ->setParameter('wireTransferReceived', Receptions::WIRE_TRANSFER_STATUS_RECEIVED)
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
            ->setParameter('assignmentType', [Receptions::STATUS_MANUALLY_ASSIGNED, Receptions::STATUS_AUTO_ASSIGNED], Connection::PARAM_INT_ARRAY)
            ->setParameter('earlyRepayment', Receptions::REPAYMENT_TYPE_EARLY)
            ->setParameter('wireTransfer', Receptions::TYPE_WIRE_TRANSFER)
            ->setParameter('wireTransferReceived', Receptions::WIRE_TRANSFER_STATUS_RECEIVED);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return Receptions[]
     */
    public function findNonAttributed()
    {
        $qb = $this->createQueryBuilder('r');
        $qb->where('r.idClient IS NULL')
           ->andWhere('r.idProject IS NULL')
           ->andWhere('r.type IN (:types)')
           ->andWhere(
               $qb->expr()->orX(
                   'r.type = ' . Receptions::TYPE_DIRECT_DEBIT . ' AND r.statusPrelevement = ' . Receptions::DIRECT_DEBIT_STATUS_SENT,
                   'r.type = ' . Receptions::TYPE_WIRE_TRANSFER . ' AND r.statusVirement = ' . Receptions::WIRE_TRANSFER_STATUS_RECEIVED
               ))
            ->orderBy('r.idReception', 'DESC')
            ->setParameter('types', [Receptions::TYPE_DIRECT_DEBIT, Receptions::TYPE_WIRE_TRANSFER], Connection::PARAM_INT_ARRAY);

        return $qb->getQuery()->getResult();
    }
}
