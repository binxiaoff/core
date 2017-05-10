<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\librairies\CacheKeys;

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
                AND l.id_lender = :lenderId';

        $statement = $this->getEntityManager()->getConnection()->executeCacheQuery(
            $query,
            ['lenderId' => $wallet->getId(), 'loanStatus' => \loans::STATUS_ACCEPTED, 'projectStatus' => \projects_status::REMBOURSEMENT],
            ['lenderId' => \PDO::PARAM_INT, 'loanStatus' => \PDO::PARAM_INT, 'projectStatus' => \PDO::PARAM_INT],
            new \Doctrine\DBAL\Cache\QueryCacheProfile(CacheKeys::SHORT_TIME, __FUNCTION__)
        );
        $result    = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return (int) current($result[0]);
    }

    /**
     * @param int $lenderId
     *
     * @return array
     */
    public function countProjectsForLenderByRegion($lenderId)
    {
        $bind = ['lenderId' => $lenderId];
        $type = ['lenderId' => \PDO::PARAM_INT];

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
                            companies.zip AS cp,
                            loans.amount,
                            loans.rate
                          FROM loans 
                              INNER JOIN wallet w ON loans.id_lender = w.id
                              INNER JOIN projects ON loans.id_project = projects.id_project
                              INNER JOIN companies ON projects.id_company = companies.id_company
                          WHERE wallet.id = :lenderId ) AS client_base
                    GROUP BY insee_region_code';

        $statement = $this->getEntityManager()->getConnection()->executeQuery($query, $bind, $type);
        $regionsCount = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $regionsCount;
    }

    /**
     * @param Projects  $project
     * @param Clients[] $clients
     *
     * @return Loans[]
     */
    public function findLoansByClients($project, array $clients)
    {
        $qb = $this->createQueryBuilder('l');
        $qb->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'w.id = l.idLender')
           ->where('l.idProject = :project')
           ->andWhere('w.idClient in (:clients)')
           ->setParameter('project', $project)
           ->setParameter('clients', $clients);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Projects  $project
     * @param Clients[] $clients
     *
     * @return Loans[]
     */
    public function getLoansSumByClients($project, array $clients)
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('SUM(l.amount)')
           ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'w.id = l.idLender')
           ->where('l.idProject = :project')
           ->andWhere('w.idClient in (:clients)')
           ->setParameter('project', $project)
           ->setParameter('clients', $clients);

        return $qb->getQuery()->getSingleScalarResult();
    }
}
