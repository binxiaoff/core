<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class VirementsRepository extends EntityRepository
{

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
