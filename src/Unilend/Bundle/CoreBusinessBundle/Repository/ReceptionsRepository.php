<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;

class ReceptionsRepository extends EntityRepository
{
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
}
