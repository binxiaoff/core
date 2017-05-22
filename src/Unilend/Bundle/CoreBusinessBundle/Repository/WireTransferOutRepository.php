<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;

class WireTransferOutRepository extends EntityRepository
{
    /**
     * @param int       $status
     * @param \DateTime $dateTime
     *
     * @return Virements[]
     */
    public function findWireTransferBefore($status, \DateTime $dateTime)
    {
        $qb = $this->createQueryBuilder('wto');
        $qb->where('wto.status = :status')
           ->andWhere('wto.added <= :added')
           ->setParameter('status', $status)
           ->setParameter('added', $dateTime);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param integer|Clients $client
     * @param array           $status
     *
     * @return Virements[]
     */
    public function findWireTransferToThirdParty($client, array $status)
    {
        $qb = $this->createQueryBuilder('wto');
        $qb->innerJoin('UnilendCoreBusinessBundle:BankAccount', 'ba', Join::WITH, 'wto.bankAccount = ba.id')
           ->where('ba.idClient != wto.idClient')
           ->andWhere('wto.idClient = :client')
           ->andWhere('wto.status in (:status)')
           ->orderBy('wto.added', 'DESC')
           ->setParameter('client', $client)
           ->setParameter('status', $status, Connection::PARAM_INT_ARRAY);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Virements[]
     */
    public function findWireTransferReadyToSend()
    {
        $qb = $this->createQueryBuilder('wto');
        $qb->where('wto.status = :ready')
           ->andWhere('wto.addedXml IS NULL')
           ->andWhere('wto.transferAt IS NULL OR wto.transferAt <= :today')
           ->setParameter('ready', Virements::STATUS_VALIDATED)
           ->setParameter('today', new \DateTime());

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Virements $wireTransferOut
     *
     * @return bool
     */
    public function isBankAccountValidatedOnceTime(Virements $wireTransferOut)
    {
        $qb = $this->createQueryBuilder('wto');
        $qb->select('COUNT(wto)')
           ->where('wto.bankAccount = :bankAccount')
           ->andWhere('wto.idClient = :client')
           ->andWhere('wto.status in (:status)')
           ->setParameter('client', $wireTransferOut->getClient())
           ->setParameter('bankAccount', $wireTransferOut->getBankAccount())
           ->setParameter('status', [Virements::STATUS_VALIDATED, Virements::STATUS_SENT], Connection::PARAM_INT_ARRAY);

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @param int       $status
     * @param int|null  $type
     *
     * @return array
     */
    public function sumWireTransferOutByDay(\DateTime $start, \DateTime $end, $status, $type = null)
    {
        $start->setTime(00, 00, 00);
        $end->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('v');
        $qb->select('ROUND(SUM(v.montant) / 100, 2) AS amount')
            ->addSelect('DATE(v.added) AS date')
            ->where('v.added BETWEEN :start AND :end')
            ->andWhere('v.status = :status')
            ->groupBy('date')
            ->orderBy('DATE(v.added)', 'ASC')
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'))
            ->setParameter('status', $status);

        if (null !== $type) {
            $qb->andWhere('v.type = :type')
                ->setParameter('type', $type);
        }
        $result = $qb->getQuery()->getResult();
        $sums = [];
        foreach ($result as $row) {
            $sums[$row['date']] = $row['amount'];
        }

        return $sums;
    }

    /**
     * @param int      $year
     * @param int      $status
     * @param null|int $type
     *
     * @return array
     */
    public function sumWireTransferOutByMonth($year, $status, $type = null)
    {
        $qb = $this->createQueryBuilder('v');
        $qb->select('ROUND(SUM(v.montant) / 100, 2) AS amount')
            ->addSelect('MONTH(v.added) AS month')
            ->where('YEAR(v.added) = :year')
            ->andWhere('v.status = :status')
            ->groupBy('month')
            ->orderBy('MONTH(v.added)', 'ASC')
            ->setParameter('year', $year)
            ->setParameter('status', $status);

        if (null !== $type) {
            $qb->andWhere('v.type = :type')
                ->setParameter('type', $type);
        }
        $result = $qb->getQuery()->getResult();
        $sums = [];
        foreach ($result as $row) {
            $sums[$row['month']] = $row['amount'];
        }

        return $sums;
    }
}
