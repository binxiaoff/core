<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\ProjectParticipationOffer;

/**
 * @method ProjectParticipationOffer|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipationOffer|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipationOffer[]    findAll()
 * @method ProjectParticipationOffer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectOfferRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipationOffer::class);
    }

    /**
     * @param ProjectParticipationOffer $projectOffer
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectParticipationOffer $projectOffer): void
    {
        $this->getEntityManager()->persist($projectOffer);
        $this->getEntityManager()->flush();
    }
}
