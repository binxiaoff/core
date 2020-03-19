<?php

declare(strict_types=1);

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Unilend\Entity\Company;
use Unilend\Entity\Staff;

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
    public function findOneByClientEmailAndCompany(string $email, Company $company): ?Staff
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.client', 'c')
            ->where(
                'c.email = :email',
                's.company = :company'
            )
            ->setParameters(['email' => $email, 'company' => $company])
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
