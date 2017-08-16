<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class LogginConnectionAdminRepository extends EntityRepository
{
    /**
     * @param string    $ip
     * @param \DateTime $start
     *
     * @return int
     */
    public function countFailedAttemptsSince($ip, \DateTime $start)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select('COUNT(log.idLogginConnectionAdmin)')
            ->from('UnilendCoreBusinessBundle:LogginConnectionAdmin', 'log')
            ->where('log.ip = :ip')
            ->andWhere('log.idUser IS NULL')
            ->andWhere('log.dateConnexion >= :start')
            ->setParameter('ip', $ip)
            ->setParameter('start', $start);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
