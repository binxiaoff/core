<?php

declare(strict_types=1);

namespace Unilend\Repository;

use DateInterval;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Unilend\Entity\LoginLog;

/**
 * @method LoginLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoginLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoginLog[]    findAll()
 * @method LoginLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginLog::class);
    }

    /**
     * @param string       $ipAddress
     * @param DateInterval $period
     *
     * @throws Exception
     *
     * @return int
     */
    public function countLastFailuresByIp(string $ipAddress, DateInterval $period): int
    {
        $queryBuilder = $this->createQueryBuilder('ll');
        $queryBuilder
            ->select('COUNT(ll)')
            ->where('ll.ip = :ip')
            ->andWhere('ll.added >= :period')
            ->setParameter('ip', $ipAddress)
            ->setParameter('period', (new \DateTime())->sub($period))
        ;

        try {
            return (int) $queryBuilder->getQuery()->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return 0;
        }
    }
}
