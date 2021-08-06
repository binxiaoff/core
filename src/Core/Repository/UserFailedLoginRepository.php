<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Unilend\Core\Entity\UserFailedLogin;

/**
 * @method UserFailedLogin|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserFailedLogin|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserFailedLogin[]    findAll()
 * @method UserFailedLogin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserFailedLoginRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFailedLogin::class);
    }

    /**
     * @throws Exception
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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(UserFailedLogin $failedLogin): void
    {
        $this->getEntityManager()->persist($failedLogin);
        $this->getEntityManager()->flush();
    }
}
