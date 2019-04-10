<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\UnexpectedResultException;
use Unilend\Entity\LoginLog;

class LoginLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginLog::class);
    }

    /**
     * @param string        $ipAddress
     * @param \DateInterval $period
     *
     * @return int
     */
    public function countLastFailuresByIp(string $ipAddress, \DateInterval $period): int
    {
        $queryBuilder = $this->createQueryBuilder('ll');
        $queryBuilder
            ->select('COUNT(ll)')
            ->where('ll.ip = :ip')
            ->andWhere('ll.added >= :period')
            ->setParameter('ip', $ipAddress)
            ->setParameter('period', (new \DateTime())->sub($period));

        try {
            return $queryBuilder->getQuery()->getSingleScalarResult();
        } catch (UnexpectedResultException $exception) {
            return 0;
        }
    }
}
