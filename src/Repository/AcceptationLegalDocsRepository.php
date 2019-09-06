<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{Mapping\OrderBy, NonUniqueResultException, ORMException, OptimisticLockException, Query};
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\AcceptationsLegalDocs;

/**
 * @method AcceptationsLegalDocs|null find($id, $lockMode = null, $lockVersion = null)
 * @method AcceptationsLegalDocs|null findOneBy(array $criteria, array $orderBy = null)
 * @method AcceptationsLegalDocs[]    findAll()
 * @method AcceptationsLegalDocs[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcceptationLegalDocsRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcceptationsLegalDocs::class);
    }

    /**
     * @param AcceptationsLegalDocs $acceptationsLegalDocs
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(AcceptationsLegalDocs $acceptationsLegalDocs): void
    {
        $this->getEntityManager()->persist($acceptationsLegalDocs);
        $this->getEntityManager()->flush();
    }

    /**
     * @param int $limit
     *
     * @return AcceptationsLegalDocs[]
     */
    public function findByIdLegalDocWithoutPfd(int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('ald');
        $queryBuilder
            ->where('ald.idLegalDoc IN (:version)')
            ->andWhere('ald.relativeFilePath IS NULL')
            ->orderBy('ald.idAcceptation', 'ASC')
            ->setMaxResults($limit)
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param UserInterface $user
     *
     * @throws NonUniqueResultException
     *
     * @return AcceptationsLegalDocs|null
     */
    public function findClientsLastSigned(UserInterface $user): ?AcceptationsLegalDocs
    {
        $queryBuilder = $this->createQueryBuilder('ald');
        $queryBuilder->where('ald.client = :client')
            ->orderBy('ald.added', 'DESC')
            ->setParameter('client', $user)
            ->setMaxResults(1)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
