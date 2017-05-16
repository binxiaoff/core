<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
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

        $qb = $this->createQueryBuilder("r");
        $qb->andWhere('r.added BETWEEN :from AND :to')
           ->setParameter('from', $from)
           ->setParameter('to', $to);
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * Get all assigned the direct debts or wire transfers to borrowers
     *
     * @return Users[]
     */
    public function getBorrowerAttributions()
    {
        $qb = $this->createQueryBuilder('r');
        $qb->andWhere('r.idProject IS NOT NULL')
           ->andWhere('r.type = :directDebit AND r.statusPrelevement = :directDebitSent OR r.type = :wireTransfer AND r.statusVirement = :wireTransferReceived')
           ->setParameter('directDebit', Receptions::TYPE_DIRECT_DEBIT)
           ->setParameter('directDebitSent', Receptions::DIRECT_DEBIT_STATUS_SENT)
           ->setParameter('wireTransfer', Receptions::TYPE_WIRE_TRANSFER)
           ->setParameter('wireTransferReceived', Receptions::WIRE_TRANSFER_STATUS_RECEIVED)
           ->orderBy('r.idReception', 'DESC');
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * Get all assigned the direct debts or wire transfers to lenders
     *
     * @return Users[]
     */
    public function getLenderAttributions()
    {
        $qb = $this->createQueryBuilder('r');
        $qb->andWhere('r.idClient IS NOT NULL')
           ->andWhere('r.idProject IS NULL')
           ->andWhere('r.type = :directDebit AND r.statusPrelevement = :directDebitSent OR r.type = :wireTransfer AND r.statusVirement = :wireTransferReceived')
           ->setParameter('directDebit', Receptions::TYPE_DIRECT_DEBIT)
           ->setParameter('directDebitSent', Receptions::DIRECT_DEBIT_STATUS_SENT)
           ->setParameter('wireTransfer', Receptions::TYPE_WIRE_TRANSFER)
           ->setParameter('wireTransferReceived', Receptions::WIRE_TRANSFER_STATUS_RECEIVED)
           ->orderBy('r.idReception', 'DESC');
        $result = $qb->getQuery()->getResult();

        return $result;
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
