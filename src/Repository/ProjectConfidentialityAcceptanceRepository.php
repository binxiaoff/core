<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\ProjectConfidentialityAcceptance;

/**
 * @method ProjectConfidentialityAcceptance|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectConfidentialityAcceptance|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectConfidentialityAcceptance[]    findAll()
 * @method ProjectConfidentialityAcceptance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectConfidentialityAcceptanceRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectConfidentialityAcceptance::class);
    }

    /**
     * @param ProjectConfidentialityAcceptance $projectConfidentialityAcceptance
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectConfidentialityAcceptance $projectConfidentialityAcceptance)
    {
        $this->getEntityManager()->persist($projectConfidentialityAcceptance);
        $this->getEntityManager()->flush();
    }
}
