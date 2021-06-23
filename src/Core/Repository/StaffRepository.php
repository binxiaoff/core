<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Staff;

/**
 * @method Staff|null find($id, $lockMode = null, $lockVersion = null)
 * @method Staff|null findOneBy(array $criteria, array $orderBy = null)
 * @method Staff[]    findAll()
 * @method Staff[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StaffRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Staff::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Staff $staff): void
    {
        $this->getEntityManager()->persist($staff);
        $this->getEntityManager()->flush();
    }

    /**
     * @throws ORMException
     */
    public function refresh(Staff $staff): void
    {
        $this->getEntityManager()->refresh($staff);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByEmailAndCompany(string $email, Company $company): ?Staff
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.user', 'u')
            ->innerJoin('s.team', 't')
            ->leftJoin('t.incomingEdges', 'i')
            ->where(
                'u.email = :email',
                's.team = :rootTeam OR i.ancestor = :rootTeam'
            )
            ->setParameters(['email' => $email, 'rootTeam' => $company->getRootTeam()])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @throws ORMException
     */
    public function persist(Staff $staff)
    {
        $this->getEntityManager()->persist($staff);
    }
}
