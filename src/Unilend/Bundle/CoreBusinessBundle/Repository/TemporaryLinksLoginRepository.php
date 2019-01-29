<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, TemporaryLinksLogin};

class TemporaryLinksLoginRepository extends EntityRepository
{
    /**
     * @param Clients $client
     * @param string  $lifetime
     *
     * @return string
     */
    public function generateTemporaryLink(Clients $client, string $lifetime = TemporaryLinksLogin::PASSWORD_TOKEN_LIFETIME_SHORT): string
    {
        $token      = bin2hex(openssl_random_pseudo_bytes(16));
        $expiryDate = (new \DateTime('NOW'))->add(new \DateInterval('P' . $lifetime));

        $temporaryLink = new TemporaryLinksLogin();
        $temporaryLink
            ->setIdClient($client)
            ->setToken($token)
            ->setExpires($expiryDate);

        $this->getEntityManager()->persist($temporaryLink);
        $this->getEntityManager()->flush($temporaryLink);

        return $token;
    }

    /**
     * @param Clients $client
     */
    public function revokeTemporaryLinks(Clients $client): void
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            ->update('UnilendCoreBusinessBundle:TemporaryLinksLogin', 't')
            ->set('t.expires', ':now')
            ->where('t.idClient = :client')
            ->andWhere($queryBuilder->expr()->gt('t.expires', ':now'))
            ->setParameter('client', $client)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
