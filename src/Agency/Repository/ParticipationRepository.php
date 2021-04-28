<?php

declare(strict_types=1);

namespace Unilend\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Agency\Entity\Participation;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Company;

/**
 * @method ParticipationRepository|null find($id, $lockMode = null, $lockVersion = null)
 * @method ParticipationRepository|null findOneBy(array $criteria, array $orderBy = null)
 * @method ParticipationRepository[]    findAll()
 * @method ParticipationRepository[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Participation::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByProjectAndCompany(Project $project, Company $company): ?Participation
    {
        return $this->createQueryBuilder('p')
            ->where('p.project = :project')
            ->andWhere('p.participant = :company')
            ->setParameters([
                'project' => $project,
                'company' => $company,
            ])
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}
