<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Unilend\Core\Entity\TemporaryToken;
use Unilend\Core\Entity\User;

/**
 * @method TemporaryToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method TemporaryToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method TemporaryToken[]    findAll()
 * @method TemporaryToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemporaryTokenRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemporaryToken::class);
    }

    /**
     * @param TemporaryToken $temporaryToken
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(TemporaryToken $temporaryToken): void
    {
        $this->persist($temporaryToken);
        $this->getEntityManager()->flush();
    }

    /**
     * @param TemporaryToken $temporaryToken
     *
     * @throws ORMException
     */
    public function persist(TemporaryToken $temporaryToken): void
    {
        $this->getEntityManager()->persist($temporaryToken);
    }

    /**
     * @param User $user
     *
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
}
