<?php

declare(strict_types=1);

namespace KLS\Core\Repository;

use DateInterval;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use JsonException;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\CompanyStatus;
use KLS\Core\Entity\HubspotContact;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\StaffStatus;
use KLS\Core\Entity\Team;
use KLS\Core\Entity\TeamEdge;
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
            ->orderBy('u.added', 'ASC')
        ;

        return $qb->setMaxResults($limit)->getQuery()->getResult();
    }

    public function findHubspotUsersToUpdate(int $limit): ?array
    {
        $date = new DateTimeImmutable();

        $qb = $this->createQueryBuilder('u')
            ->innerJoin(HubspotContact::class, 'hc', Join::WITH, 'u.id = hc.user')
            ->where('u.updated > hc.synchronized')
            ->orWhere('hc.synchronized < :dateSubOneDay')
            ->orderBy('hc.synchronized', 'ASC')
            ->setParameter('dateSubOneDay', $date->sub(new DateInterval('P1D')))
        ;

        return $qb->setMaxResults($limit)->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findActiveUsersPerCompany(Company $company): ?array
    {
        return $this->createQueryBuilder('u')
            ->select('ROUND(100 - (COUNT(u.id) - SUM(case when u.password IS NULL then 0 else 1 end)) / COUNT(u.id) * 100) as user_init_percentage')
            ->innerJoin(UserStatus::class, 'us', Join::WITH, 'u.currentStatus = us.id')
            ->innerJoin(Staff::class, 's', Join::WITH, 'u.id = s.user')
            ->innerJoin(StaffStatus::class, 'ss', Join::WITH, 's.id = ss.staff')
            ->innerJoin(Team::class, 't', Join::WITH, 's.team = t.id')
            ->leftJoin(TeamEdge::class, 'te', Join::WITH, 's.team = te.descendent')
            ->innerJoin(Company::class, 'c', Join::WITH, 's.team = c.rootTeam OR te.ancestor = c.rootTeam')
            ->innerJoin(CompanyStatus::class, 'cs', Join::WITH, 'c.currentStatus = cs.id')
            ->where('ss.status = :status')
            ->andWhere('c = :company')
            ->setParameters([
                'status'  => StaffStatus::STATUS_ACTIVE,
                'company' => $company,
            ])
            ->getQuery()
            ->getSingleResult()
            ;
    }
}
