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
     * @param Clients $client
     *
     * @throws Exception
     */
    public function expireTemporaryTokens(Clients $client): void
    {
        if ($client->getId()) {
            $this->createQueryBuilder('t')
                ->update(TemporaryToken::class, 't')
                ->set('t.expires', ':now')
                ->set('t.updated', ':now')
                ->where('t.client = :client')
                ->andWhere('t.expires > :now')
                ->setParameter('client', $client)
                ->setParameter('now', new DateTimeImmutable())
                ->getQuery()
                ->execute()
            ;
        }
    }
}
