<?php

declare(strict_types=1);

namespace KLS\Syndication\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\User;
use KLS\Syndication\Agency\Entity\ParticipationMember;
use KLS\Syndication\Agency\Entity\Project;

/**
 * @method ParticipationMemberRepository|null find($id, $lockMode = null, $lockVersion = null)
 * @method ParticipationMemberRepository|null findOneBy(array $criteria, array $orderBy = null)
 * @method ParticipationMemberRepository[]    findAll()
 * @method ParticipationMemberRepository[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticipationMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, ParticipationMember::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByProjectAndCompanyAndUserAndActive(Project $project, Company $company, User $user): ?ParticipationMember
    {
        return $this->createQueryBuilder('pm')
            ->innerJoin('pm.participation', 'p')
            ->innerJoin('p.pool', 'po')
            ->where('po.project = :project')
            ->andWhere('pm.user = :user')
            ->andWhere('pm.archivingDate IS NULL')
            ->andWhere('p.participant = :company')
            ->setParameters([
                'user'    => $user,
                'project' => $project,
                'company' => $company,
            ])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
