<?php

declare(strict_types=1);

namespace Unilend\Repository;

use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Unilend\Entity\ClientFailedLogin;

/**
 * @method ClientFailedLogin|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientFailedLogin|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientFailedLogin[]    findAll()
 * @method ClientFailedLogin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientFailedLoginRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientFailedLogin::class);
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
        $queryBuilder = $this->createQueryBuilder('ll')
            ->select('COUNT(ll)')
            ->where('ll.ip = :ip')
            ->andWhere('ll.added >= :period')
            ->setParameter('ip', $ipAddress)
            ->setParameter('period', (new DateTime())->sub($period))
        ;

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param ClientFailedLogin $failedLogin
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ClientFailedLogin $failedLogin): void
    {
        $this->getEntityManager()->persist($failedLogin);
        $this->getEntityManager()->flush();
    }
}
