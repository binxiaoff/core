<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Core\Entity\AcceptationsLegalDocs;

/**
 * @method AcceptationsLegalDocs|null find($id, $lockMode = null, $lockVersion = null)
 * @method AcceptationsLegalDocs|null findOneBy(array $criteria, array $orderBy = null)
 * @method AcceptationsLegalDocs[]    findAll()
 * @method AcceptationsLegalDocs[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcceptationLegalDocsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcceptationsLegalDocs::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(AcceptationsLegalDocs $acceptationsLegalDocs): void
    {
        $this->getEntityManager()->persist($acceptationsLegalDocs);
        $this->getEntityManager()->flush();
    }

    /**
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
     * @throws NonUniqueResultException
     */
    public function findUsersLastSigned(UserInterface $user): ?AcceptationsLegalDocs
    {
        $queryBuilder = $this->createQueryBuilder('ald');
        $queryBuilder->where('ald.user = :user')
            ->orderBy('ald.added', 'DESC')
            ->setParameter('user', $user)
            ->setMaxResults(1)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
