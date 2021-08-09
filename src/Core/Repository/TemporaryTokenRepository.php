<?php

declare(strict_types=1);

namespace KLS\Core\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use KLS\Core\Entity\TemporaryToken;
use KLS\Core\Entity\User;

/**
 * @method TemporaryToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method TemporaryToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method TemporaryToken[]    findAll()
 * @method TemporaryToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemporaryTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemporaryToken::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(TemporaryToken $temporaryToken): void
    {
        $this->persist($temporaryToken);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws ORMException
     */
    public function persist(TemporaryToken $temporaryToken): void
    {
        $this->getEntityManager()->persist($temporaryToken);
    }

    /**
     * @throws Exception
     */
    public function expireTemporaryTokens(User $user): void
    {
        if ($user->getId()) {
            $this->createQueryBuilder('t')
                ->update(TemporaryToken::class, 't')
                ->set('t.expires', ':now')
                ->set('t.updated', ':now')
                ->where('t.user = :user')
                ->andWhere('t.expires > :now')
                ->setParameter('user', $user)
                ->setParameter('now', new DateTimeImmutable())
                ->getQuery()
                ->execute()
            ;
        }
    }

    public function findOneActiveByUser(User $user): ?TemporaryToken
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->andWhere('t.expires < :now')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->setParameter('now', new DateTimeImmutable())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
