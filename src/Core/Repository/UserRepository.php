<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\{Expr\Join};
use Doctrine\Persistence\ManagerRegistry;
use JsonException;
use PDO;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\User;
use Unilend\Core\Entity\UserStatus;
use Unilend\Syndication\Entity\ProjectParticipation;

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
     **@throws NonUniqueResultException
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
     **@throws NonUniqueResultException
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

    public function findDefaultConcernedUsers(ProjectParticipation $projectParticipation): array
    {
        $queryBuilder = $this->createQueryBuilder('users')
            ->innerJoin('users.staff', 'staff')
            ->where('staff.company = :company')
            ->setParameters([
                'company' => $projectParticipation->getParticipant(),
            ])
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}
