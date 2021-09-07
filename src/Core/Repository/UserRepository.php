<?php

declare(strict_types=1);

namespace KLS\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\{Expr\Join};
use Doctrine\Persistence\ManagerRegistry;
use JsonException;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\HubspotContact;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Core\Entity\UserStatus;
use PDO;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    private const MAX_USER_LOAD = 10;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @param int|User $idUser
     *
     * @throws NonUniqueResultException
     *
     * @return mixed
     */
    public function getCompany($idUser)
    {
        if ($idUser instanceof User) {
            $idUser = $idUser->getId();
        }

        $qb = $this->createQueryBuilder('c');
        $qb->select('co')
            ->innerJoin(Company::class, 'co', Join::WITH, 'c.idUser = co.idUserOwner')
            ->where('c.idUser = :idUser')
            ->setParameter('idUser', $idUser)
        ;
        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findGrantedLoginAccountByEmail(string $email): ?User
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->innerJoin(UserStatus::class, 'csh', Join::WITH, 'c.currentStatus = csh.id')
            ->where('c.email = :email')
            ->andWhere('csh.status IN (:status)')
            ->setParameter('email', $email, PDO::PARAM_STR)
            ->setParameter('status', UserStatus::GRANTED_LOGIN)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @throws JsonException
     *
     * @return User[]
     */
    public function findByStaffRoles(Company $company, array $roles): iterable
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->innerJoin(Staff::class, 's', Join::WITH, 'c.idUser = s.user')
            ->where('JSON_CONTAINS(s.roles, :role) = 1')
            ->andWhere('s.company = :company')
            ->setParameter('role', \json_encode($roles, JSON_THROW_ON_ERROR))
            ->setParameter('company', $company)
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    public function findHubspotUsersToCreate(int $limit): ?array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin(HubspotContact::class, 'hc', Join::WITH, 'u.id = hc.user')
            ->where('hc.id IS NULL')
        ;

        return $qb->setMaxResults($limit)->getQuery()->getResult();
    }

    public function findHubspotUsersToUpdate(int $limit): ?array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin(HubspotContact::class, 'hc', Join::WITH, 'u.id = hc.user')
            ->where('hc.id IS NOT NULL')
            ->andWhere('u.updated > hc.synchronized')
            ->orderBy('hc.synchronized', 'DESC')
        ;

        return $qb->setMaxResults($limit)->getQuery()->getResult();
    }
}
