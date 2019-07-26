<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\LoginConnectionAdmin;

class LoginConnectionAdminRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginConnectionAdmin::class);
    }

    /**
     * @param string    $ip
     * @param \DateTime $start
     *
     * @return int
     */
    public function countFailedAttemptsSince($ip, \DateTime $start)
    {
        $queryBuilder = $this->createQueryBuilder('log');
        $queryBuilder
            ->select('COUNT(log.idLoginConnectionAdmin)')
            ->where('log.ip = :ip')
            ->andWhere('log.idUser IS NULL')
            ->andWhere('log.dateConnexion >= :start')
            ->setParameter('ip', $ip)
            ->setParameter('start', $start)
        ;

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
