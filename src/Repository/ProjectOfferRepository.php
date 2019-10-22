<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\ProjectOffer;

/**
 * @method ProjectOffer|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectOffer|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectOffer[]    findAll()
 * @method ProjectOffer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectOfferRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectOffer::class);
    }

    /**
     * @param ProjectOffer $projectOffer
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectOffer $projectOffer): void
    {
        $this->getEntityManager()->persist($projectOffer);
        $this->getEntityManager()->flush();
    }
}
