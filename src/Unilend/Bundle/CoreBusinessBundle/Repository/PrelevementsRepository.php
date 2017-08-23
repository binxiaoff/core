<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

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
            ->setParameter('statusPending', \prelevements::STATUS_PENDING);
        $result = $qb->getQuery()->getResult();
        $sums = [];
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
            ->setParameter('statusPending', \prelevements::STATUS_PENDING);
        $result = $qb->getQuery()->getResult();
        $sums = [];
        foreach ($result as $row) {
            $sums[$row['month']] = $row['amount'];
        }

        return $sums;
    }
}
