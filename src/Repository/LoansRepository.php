<?php

namespace Unilend\Repository;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{AddressType, ClientAddress, Clients, Companies, Echeanciers, Loans, LoanTransfer, Projects, ProjectsStatus, Transfer, UnderlyingContract, Wallet, WalletType};
use Unilend\CacheKeys;

class LoansRepository extends EntityRepository
{
    /**
     * @param Wallet $wallet
     *
     * @return int
     *
     * @throws \Exception
     */
    public function sumLoansOfProjectsInRepayment(Wallet $wallet)
    {
        if (WalletType::LENDER !== $wallet->getIdType()->getLabel()) {
            throw new \Exception('Wallet should be of type Lender');
        }

        $query = '
            SELECT IFNULL(SUM(l.amount), 0)
            FROM loans l
            INNER JOIN projects p ON l.id_project = p.id_project
            WHERE l.status = :loanStatus
                AND p.status >= :projectStatus
                AND l.id_wallet = :walletId';

        $statement = $this->getEntityManager()->getConnection()->executeCacheQuery(
            $query,
            ['walletId' => $wallet->getId(), 'loanStatus' => Loans::STATUS_ACCEPTED, 'projectStatus' => ProjectsStatus::STATUS_REPAYMENT],
            ['walletId' => \PDO::PARAM_INT, 'loanStatus' => \PDO::PARAM_INT, 'projectStatus' => \PDO::PARAM_INT],
            new QueryCacheProfile(CacheKeys::SHORT_TIME, __FUNCTION__)
        );
        $result    = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return (int) current($result[0]);
    }

    /**
     * @param int $walletId
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function countProjectsForLenderByRegion(int $walletId): array
    {
        $bind = ['walletId' => $walletId, 'mainAddress' => AddressType::TYPE_MAIN_ADDRESS];

        $query = 'SELECT
                      CASE
                      WHEN LEFT(client_base.cp, 2) IN (08, 10, 51, 52, 54, 55, 57, 67, 68, 88)
                        THEN "44"
                      WHEN LEFT(client_base.cp, 2) IN (16, 17, 19, 23, 24, 33, 40, 47, 64, 79, 86, 87)
                        THEN "75"
                      WHEN LEFT(client_base.cp, 2) IN (01, 03, 07, 15, 26, 38, 42, 43, 63, 69, 73, 74)
                        THEN "84"
                      WHEN LEFT(client_base.cp, 2) IN (21, 25, 39, 58, 70, 71, 89, 90)
                        THEN "27"
                      WHEN LEFT(client_base.cp, 2) IN (22, 29, 35, 56)
                        THEN "53"
                      WHEN LEFT(client_base.cp, 2) IN (18, 28, 36, 37, 41, 45)
                        THEN "24"
                      WHEN LEFT(client_base.cp, 2) IN (20)
                        THEN "94"
                      WHEN LEFT(client_base.cp, 3) IN (971)
                        THEN "01"
                      WHEN LEFT(client_base.cp, 3) IN (973)
                        THEN "03"
                      WHEN LEFT(client_base.cp, 2) IN (75, 77, 78, 91, 92, 93, 94, 95)
                        THEN "11"
                      WHEN LEFT(client_base.cp, 3) IN (974)
                        THEN "04"
                      WHEN LEFT(client_base.cp, 2) IN (09, 11, 12, 30, 31, 32, 34, 46, 48, 65, 66, 81, 82)
                        THEN "76"
                      WHEN LEFT(client_base.cp, 3) IN (972)
                        THEN "02"
                      WHEN LEFT(client_base.cp, 3) IN (976)
                        THEN "06"
                      WHEN LEFT(client_base.cp, 2) IN (02, 59, 60, 62, 80)
                        THEN "32"
                      WHEN LEFT(client_base.cp, 2) IN (14, 27, 50, 61, 76)
                        THEN "28"
                      WHEN LEFT(client_base.cp, 2) IN (44, 49, 53, 72, 85)
                        THEN "52"
                      WHEN LEFT(client_base.cp, 2) IN (04, 05, 06, 13, 83, 84)
                        THEN "93"
                      ELSE "0"
                      END AS insee_region_code,
                      COUNT(*) AS count,
                      SUM(client_base.amount) / 100 AS loaned_amount,
                      AVG(client_base.rate) AS average_rate
                    FROM (SELECT
                            ca.zip AS cp,
                            l.amount,
                            l.rate
                          FROM loans l
                              INNER JOIN wallet w ON l.id_wallet = w.id
                              INNER JOIN projects p ON l.id_project = p.id_project
                              INNER JOIN company_address ca ON p.id_company = ca.id_company AND ca.date_archived IS NULL
                              INNER JOIN address_type at ON ca.id_type = at.id AND at.label = :mainAddress
                          WHERE w.id = :walletId) AS client_base
                    GROUP BY insee_region_code';

        $statement    = $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, $bind);
        $regionsCount = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $regionsCount;
    }

    /**
     * @param Projects|integer $project
     * @param Clients[]        $clients
     *
     * @return Loans[]
     */
    public function findLoansByClients($project, array $clients)
    {
        $queryBuilder = $this->createQueryBuilder('l');
        $queryBuilder->innerJoin(Wallet::class, 'w', Join::WITH, 'w.id = l.wallet')
            ->where('l.project = :project')
            ->andWhere('w.idClient IN (:clients)')
            ->setParameter('project', $project)
            ->setParameter('clients', $clients);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Projects|integer $project
     * @param Clients[]        $clients
     *
     * @return float
     */
    public function getLoansSumByClients($project, array $clients)
    {
        $queryBuilder = $this->createQueryBuilder('l');
        $queryBuilder->select('SUM(ROUND(l.amount/100, 2))')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'w.id = l.wallet')
            ->where('l.project = :project')
            ->andWhere('w.idClient IN (:clients)')
            ->setParameter('project', $project)
            ->setParameter('clients', $clients);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Projects|integer $project
     *
     * @return integer
     */
    public function getLenderNumber($project)
    {
        $queryBuilder = $this->createQueryBuilder('l');
        $queryBuilder->select('COUNT(DISTINCT l.wallet) ')
            ->where('l.project = :project')
            ->setParameter('project', $project);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet $wallet
     *
     * @return mixed
     */
    public function getDefinitelyAcceptedLoansCount(Wallet $wallet)
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->select('COUNT(DISTINCT l.idLoan)')
            ->innerJoin(Projects::class, 'p', Join::WITH, 'p.idProject = l.project')
            ->where('l.wallet = :wallet')
            ->setParameter('wallet', $wallet->getId())
            ->andWhere('l.status = :accepted')
            ->setParameter('accepted', Loans::STATUS_ACCEPTED)
            ->andWhere('p.status >= :repayment')
            ->setParameter('repayment', ProjectsStatus::STATUS_REPAYMENT);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int|Projects $project
     *
     * @return array
     */
    public function getBasicInformation($project)
    {
        $queryBuilder = $this->createQueryBuilder('l');
        $queryBuilder
            ->select('
                l.idLoan,
                c.nom AS name, c.prenom AS first_name, c.email, c.type, c.naissance AS birthday, c.telephone, c.mobile,
                com.name AS company_name,
                ca.address, ca.zip AS postal_code, ca.city,
                ROUND(l.amount / 100, 2) AS amount
            ')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'l.wallet = w.id')
            ->innerJoin(Clients::class, 'c', Join::WITH, 'c.idClient = w.idClient')
            ->leftJoin(Companies::class, 'com', Join::WITH, 'com.idClientOwner = w.idClient')
            ->leftJoin(ClientAddress::class, 'ca', Join::WITH, 'ca.id = com.idAddress')
            ->where('l.project = :project')
            ->setParameter('project', $project);

        $loans                 = $queryBuilder->getQuery()->getArrayResult();
        $loansBasicInformation = [];

        foreach ($loans as $loan) {
            $loansBasicInformation[$loan['idLoan']] = $loan;
        }

        return $loansBasicInformation;
    }

    /**
     * @param Projects $project
     *
     * @return array
     */
    public function getProjectLoans(Projects $project): array
    {
        $queryBuilder = $this->createQueryBuilder('l');
        $queryBuilder
            ->select('
                l.idLoan,
                ROUND(l.amount / 100) AS amount,
                l.rate,
                uc.label AS contractType,
                ROUND(e.montant / 100, 2) AS monthlyRepayment,
                c.idClient,
                c.hash AS clientHash,
                c.prenom AS firstName,
                c.nom AS lastName,
                co.name AS companyName,
                IDENTITY(t.idClientOrigin) AS idClientOrigin'
            )
            ->innerJoin(UnderlyingContract::class, 'uc', Join::WITH, 'l.idTypeContract = uc.idContract')
            ->innerJoin(Echeanciers::class, 'e', Join::WITH, 'l.idLoan = e.idLoan AND e.ordre = 1')
            ->innerJoin(Wallet::class, 'w', Join::WITH, 'l.wallet = w.id')
            ->innerJoin(Clients::class, 'c', Join::WITH, 'c.idClient = w.idClient')
            ->leftJoin(Companies::class, 'co', Join::WITH, 'co.idClientOwner = w.idClient')
            ->leftJoin(LoanTransfer::class, 'lt', Join::WITH, 'l.idLoan = lt.idLoan')
            ->leftJoin(Transfer::class, 't', Join::WITH, 'lt.idTransfer = t.idTransfer')
            ->where('l.project = :project')
            ->setParameter('project', $project);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * @param Projects|int $project
     *
     * @return array
     */
    public function getProjectLoanDetailsForEachLender($project): array
    {
        $queryBuilder = $this->createQueryBuilder('l');
        $queryBuilder
            ->select('
                IDENTITY(l.wallet) AS idLender,
                SUM(l.amount) AS amount,
                GROUP_CONCAT(l.idLoan) AS loans'
            )
            ->where('l.project = :idProject')
            ->andWhere('l.status = :statusAccepted')
            ->groupBy('l.wallet')
            ->setParameter('idProject', $project)
            ->setParameter('statusAccepted', Loans::STATUS_ACCEPTED);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * @param Wallet|int $wallet
     * @param \DateTime  $date
     *
     * @return int|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCountLoansForLenderBeforeDate($wallet, \DateTime $date): ?int
    {
        $queryBuilder = $this->createQueryBuilder('l');
        $queryBuilder
            ->select('COUNT(l.idLoan)')
            ->where('l.wallet = :wallet')
            ->andWhere('l.added < :date')
            ->setParameter('wallet', $wallet)
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
