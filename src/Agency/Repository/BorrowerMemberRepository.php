<?php

declare(strict_types=1);

namespace Unilend\Agency\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Agency\Entity\BorrowerMember;
use Unilend\Agency\Entity\Project;
use Unilend\Core\Entity\User;

/**
 * @method BorrowerMemberRepository|null find($id, $lockMode = null, $lockVersion = null)
 * @method BorrowerMemberRepository|null findOneBy(array $criteria, array $orderBy = null)
 * @method BorrowerMemberRepository[]    findAll()
 * @method BorrowerMemberRepository[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BorrowerMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, BorrowerMember::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function existsByProjectAndUser(Project $project, User $user): bool
    {
        $return = $this->createQueryBuilder('bm')
            ->select('bm.id')
            ->innerJoin('bm.borrower', 'b')
            ->where('b.project = :project')
            ->andWhere('bm.user = :user')
            ->setMaxResults(1)
            ->setParameters([
                'user'    => $user,
                'project' => $project,
            ])
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR)
        ;

        return $return ? true : false;
    }
}
