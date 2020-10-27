<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Entity\ProjectParticipationTrancheHistory;

/**
 * @method ProjectParticipationTrancheHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipationTrancheHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipationTrancheHistory[]    findAll()
 * @method ProjectParticipationTrancheHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectParticipationTrancheHistoryRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipationTrancheHistory::class);
    }

    // /**
    //  * @return ProjectParticipationTrancheHistory[] Returns an array of ProjectParticipationTrancheHistory objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProjectParticipationTrancheHistory
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
