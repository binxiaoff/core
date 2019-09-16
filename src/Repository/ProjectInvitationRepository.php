<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Unilend\Entity\ProjectInvitation;

/**
 * @method ProjectInvitation|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProjectInvitation|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProjectInvitation[]    findAll()
 * @method ProjectInvitation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectInvitationRepository extends ServiceEntityRepository
{
    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ProjectInvitation::class);
    }

    /**
     * @param ProjectInvitation $projectInvitation
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(ProjectInvitation $projectInvitation)
    {
        $this->getEntityManager()->persist($projectInvitation);
        $this->getEntityManager()->flush();
    }
}
