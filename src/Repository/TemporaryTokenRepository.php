<?php

declare(strict_types=1);

namespace Unilend\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Unilend\Entity\{Clients, TemporaryToken};

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
        $this->getEntityManager()->persist($temporaryToken);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Clients $client
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return string
     */
    public function generateShortTemporaryToken(Clients $client): string
    {
        $temporaryToken = TemporaryToken::generateShortToken($client);

        $this->save($temporaryToken);

        return $temporaryToken->getToken();
    }

    /**
     * @param Clients $client
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return string
     */
    public function generateLongTemporaryToken(Clients $client): string
    {
        $temporaryToken = TemporaryToken::generateLongToken($client);

        $this->save($temporaryToken);

        return $temporaryToken->getToken();
    }

    /**
     * @param Clients $client
     *
     * @throws Exception
     */
    public function revokeTemporaryTokens(Clients $client): void
    {
        $this->createQueryBuilder('t')
            ->update(TemporaryToken::class, 't')
            ->set('t.expires', ':now')
            ->where('t.client = :client')
            ->andWhere('t.expires > :now')
            ->andWhere('t.accessed IS NULL')
            ->setParameter('client', $client)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->execute()
        ;
    }
}
