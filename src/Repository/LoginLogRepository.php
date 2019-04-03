<?php

namespace Unilend\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnexpectedResultException;

class LoginLogRepository extends EntityRepository
{
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
