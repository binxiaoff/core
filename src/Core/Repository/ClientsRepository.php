<?php

declare(strict_types=1);

namespace Unilend\Core\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\{Expr\Join};
use Doctrine\ORM\{NonUniqueResultException, ORMException, OptimisticLockException};
use PDO;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\ClientStatus;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Staff;
use Unilend\Syndication\Entity\{ProjectParticipation};

/**
 * @method Clients|null find($id, $lockMode = null, $lockVersion = null)
 * @method Clients|null findOneBy(array $criteria, array $orderBy = null)
 * @method Clients[]    findAll()
 * @method Clients[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientsRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Clients::class);
    }

    /**
     * @param Clients $client
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Clients $client): void
    {
        $this->getEntityManager()->persist($client);
        $this->getEntityManager()->flush();
    }

    /**
     * @param int|Clients $idClient
     *
     * @throws NonUniqueResultException
     *
     * @return mixed
     */
    public function getCompany($idClient)
    {
        if ($idClient instanceof Clients) {
            $idClient = $idClient->getId();
        }

        $qb = $this->createQueryBuilder('c');
        $qb->select('co')
            ->innerJoin(Company::class, 'co', Join::WITH, 'c.idClient = co.idClientOwner')
            ->where('c.idClient = :idClient')
            ->setParameter('idClient', $idClient)
        ;
        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param string $email
     *
     * @throws NonUniqueResultException
     *
     * @return Clients|null
     */
    public function findGrantedLoginAccountByEmail(string $email): ?Clients
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->innerJoin(ClientStatus::class, 'csh', Join::WITH, 'c.currentStatus = csh.id')
            ->where('c.email = :email')
            ->andWhere('csh.status IN (:status)')
            ->setParameter('email', $email, PDO::PARAM_STR)
            ->setParameter('status', ClientStatus::GRANTED_LOGIN)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Company $company
     * @param array   $roles
     *
     * @return Clients[]
     */
    public function findByStaffRoles(Company $company, array $roles): iterable
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->innerJoin(Staff::class, 's', Join::WITH, 'c.idClient = s.client')
            ->where('JSON_CONTAINS(s.roles, :role) = 1')
            ->andwhere('s.company = :company')
            ->setParameter('role', json_encode($roles, JSON_THROW_ON_ERROR))
            ->setParameter('company', $company)
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param ProjectParticipation $projectParticipation
     *
     * @return array
     */
    public function findDefaultConcernedClients(ProjectParticipation $projectParticipation): array
    {
        $queryBuilder = $this->createQueryBuilder('clients')
            ->innerJoin('clients.staff', 'staff')
            ->where('staff.company = :company')
            ->andWhere(':marketSegment MEMBER OF staff.marketSegments')
            ->setParameters([
                'company'       => $projectParticipation->getParticipant(),
                'marketSegment' => $projectParticipation->getProject()->getMarketSegment(),
            ])
        ;

        return $queryBuilder->getQuery()->getResult();
    }
}
