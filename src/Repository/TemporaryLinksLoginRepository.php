<?php

namespace Unilend\Repository;

use DateInterval;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Exception;
use Unilend\Entity\{Clients, TemporaryLinksLogin};

/**
 * @method TemporaryLinksLogin|null find($id, $lockMode = null, $lockVersion = null)
 * @method TemporaryLinksLogin|null findOneBy(array $criteria, array $orderBy = null)
 * @method TemporaryLinksLogin[]    findAll()
 * @method TemporaryLinksLogin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemporaryLinksLoginRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemporaryLinksLogin::class);
    }

    /**
     * @param TemporaryLinksLogin $temporaryLinksLogin
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(TemporaryLinksLogin $temporaryLinksLogin): void
    {
        $this->getEntityManager()->persist($temporaryLinksLogin);
        $this->getEntityManager()->flush();
    }

    /**
     * @param Clients $client
     * @param string  $lifetime
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     *
     * @return string
     */
    public function generateTemporaryLink(Clients $client, string $lifetime = TemporaryLinksLogin::PASSWORD_TOKEN_LIFETIME_SHORT): string
    {
        $token      = bin2hex(openssl_random_pseudo_bytes(16));
        $expiryDate = (new \DateTime('NOW'))->add(new DateInterval('P' . $lifetime));

        $temporaryLink = new TemporaryLinksLogin();
        $temporaryLink
            ->setIdClient($client)
            ->setToken($token)
            ->setExpires($expiryDate)
        ;

        $this->getEntityManager()->persist($temporaryLink);
        $this->getEntityManager()->flush($temporaryLink);

        return $token;
    }

    /**
     * @param Clients $client
     *
     * @throws Exception
     */
    public function revokeTemporaryLinks(Clients $client): void
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            ->update(TemporaryLinksLogin::class, 't')
            ->set('t.expires', ':now')
            ->where('t.idClient = :client')
            ->andWhere($queryBuilder->expr()->gt('t.expires', ':now'))
            ->setParameter('client', $client)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute()
        ;
    }
}
