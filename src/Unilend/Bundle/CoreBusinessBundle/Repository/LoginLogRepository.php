<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class LoginLogRepository extends EntityRepository
{
    /**
     * @param string        $ipAddress
     * @param \DateInterval $period
     *
     * @return int
     */
    public function countFailuresByIp(string $ipAddress, \DateInterval $period): int
    {
        $queryBuilder = $this->createQueryBuilder('ll');
        $queryBuilder
            ->where('ll.ip = :ip')
            ->andWhere('ll.added >= :period')
            ->setParameter('ip', $ipAddress)
            ->setParameter('period', (new \DateTime())->sub($period));

        $failures = $queryBuilder->getQuery()->getResult();

        return count($failures);
    }
}
