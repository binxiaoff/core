<?php

declare(strict_types=1);

namespace Unilend\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Agency\Entity\ParticipationMember;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\User;

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
    public function existsByProjectAndCompanyAndUser(Project $project, Company $company, User $user): bool
    {
        $return = $this->createQueryBuilder('pm')
            ->select('pm.id')
            ->innerJoin('pm.participation', 'p')
            ->where('p.project = :project')
            ->andWhere('pm.user = :user')
            ->andWhere('p.participant = :company')
            ->setMaxResults(1)
            ->setParameters([
                'user'    => $user,
                'project' => $project,
                'company' => $company,
            ])
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;

        return $return ? true : false;
    }
}
