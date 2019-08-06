<?php

declare(strict_types=1);

namespace Unilend\Repository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Unilend\Entity\LoginConnectionAdmin;

/**
 * @method LoginConnectionAdmin|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoginConnectionAdmin|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoginConnectionAdmin[]    findAll()
 * @method LoginConnectionAdmin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginConnectionAdminRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginConnectionAdmin::class);
    }

    /**
     * @param string   $ip
     * @param DateTime $start
     *
     * @throws NonUniqueResultException
     *
     * @return int
     */
    public function countFailedAttemptsSince($ip, DateTime $start): int
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
