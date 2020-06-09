<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\{ORMException, OptimisticLockException};
use Unilend\Entity\ProjectParticipationTranche;

/**
 * @method ProjectParticipationTranche|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectParticipationTranche|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectParticipationTranche[]    findAll()
 * @method ProjectParticipationTranche[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrancheOfferRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectParticipationTranche::class);
    }

    /**
     * @param ProjectParticipationTranche $trancheOffer
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectParticipationTranche $trancheOffer): void
    {
        $this->getEntityManager()->persist($trancheOffer);
        $this->getEntityManager()->flush();
    }
}
