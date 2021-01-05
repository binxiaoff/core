<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\ResultSetMapping;
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
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Staff::class);
    }

    /**
     * @param Staff $staff
     *
     * @throws ORMException
     */
    public function refresh(Staff $staff): void
    {
        $this->getEntityManager()->refresh($staff);
    }

    /**
     * @param string  $email
     * @param Company $company
     *
     * @throws NonUniqueResultException
     *
     * @return Staff|null
     */
    public function findOneByEmailAndCompany(string $email, Company $company): ?Staff
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.user', 'u')
            ->innerJoin('s.team', 't')
            ->innerJoin('t.incomingEdges', 'i')
            ->where(
                'u.email = :email',
                's.team = :rootTeam OR i.ancestor = :rootTeam'
            )
            ->setParameters(['email' => $email, 'rootTeam' => $company->getRootTeam()])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
