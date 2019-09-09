<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Unilend\Entity\ClientsStatus;

class ClientStatusRepository extends ServiceEntityRepository
{
    /**
     * ClientStatusRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientsStatus::class);
    }

    /**
     * @param string $code
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     *
     * @return ClientsStatus
     */
    public function findOneByCode(string $code): ClientsStatus
    {
        return $this->createQueryBuilder('cs')
            ->where('cs.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getSingleResult()
        ;
    }
}
