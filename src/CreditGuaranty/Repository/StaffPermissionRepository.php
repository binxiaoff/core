<?php

declare(strict_types=1);

namespace Unilend\CreditGuaranty\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\Company;
use Unilend\CreditGuaranty\Entity\StaffPermission;

/**
 * @method StaffPermission|null find($id, $lockMode = null, $lockVersion = null)
 * @method StaffPermission|null findOneBy(array $criteria, array $orderBy = null)
 * @method StaffPermission[]    findAll()
 * @method StaffPermission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StaffPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, StaffPermission::class);
    }

    /**
     * @return StaffPermission[]|array
     */
    public function findParticipationAdmins(Company $company): array
    {
        return $this->createQueryBuilder('sp')
            ->innerJoin('sp.staff', 's')
            ->innerJoin('s.team', 't')
            ->leftJoin('t.incomingEdges', 'i')
            ->where('s.team = :rootTeam OR i.ancestor = :rootTeam')
            ->andWhere('sp.grantPermissions = :permission')
            ->setParameter('rootTeam', $company->getRootTeam())
            ->setParameter('permission', StaffPermission::PARTICIPANT_ADMIN_PERMISSIONS)
            ->getQuery()
            ->getResult()
            ;
    }
}
