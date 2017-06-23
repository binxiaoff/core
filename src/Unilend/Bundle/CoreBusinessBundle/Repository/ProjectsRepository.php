<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use PDO;
use Unilend\librairies\CacheKeys;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Unilend\Bundle\CoreBusinessBundle\Entity\Factures;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;

class ProjectsRepository extends EntityRepository
{
    /**
     * @param int $lenderId
     *
     * @return int
     */
    public function countCompaniesLenderInvestedIn($lenderId)
    {
        $query = '
            SELECT COUNT(DISTINCT p.id_company)
            FROM projects p
            INNER JOIN loans l ON p.id_project = l.id_project
            WHERE p.status >= :status AND l.id_lender = :lenderId';

        $statement = $this->getEntityManager()->getConnection()->executeCacheQuery(
            $query,
            ['lenderId' => $lenderId, 'status' => \projects_status::REMBOURSEMENT],
            ['lenderId' => PDO::PARAM_INT, 'status' => PDO::PARAM_INT],
            new QueryCacheProfile(CacheKeys::SHORT_TIME, md5(__METHOD__))
        );
        $result    = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();

        return (int) current($result[0]);
    }

    /**
     * @param \DateTime $dateTime
     *
     * @return Projects[];
     */
    public function findPartiallyReleasedProjects(\DateTime $dateTime)
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('UnilendCoreBusinessBundle:Projects', 'p');

        $sql = 'SELECT p.*, MIN(psh.added) as release_date, p.amount - ROUND(i.montant_ttc / 100, 2) - IF(f.release_funds IS NULL, 0, f.release_funds ) as rest_funds
                FROM projects p
                  INNER JOIN projects_status_history psh ON psh.id_project = p.id_project
                  INNER JOIN projects_status ps ON ps.id_project_status = psh.id_project_status
                  INNER JOIN factures i ON i.id_project = p.id_project
                  LEFT JOIN (SELECT o.id_project, SUM(amount) AS release_funds
                              FROM operation o
                                INNER JOIN operation_type ot ON o.id_type = ot.id
                              WHERE ot.label = :borrower_withdraw
                              GROUP BY o.id_project) f ON f.id_project = p.id_project
                WHERE ps.status = :repayment
                      AND i.type_commission = :funds_commission
                GROUP BY p.id_project
                HAVING rest_funds > 0 AND release_date <= :date';

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameters([
            'borrower_withdraw' => OperationType::BORROWER_WITHDRAW,
            'repayment'         => ProjectsStatus::REMBOURSEMENT,
            'funds_commission'  => Factures::TYPE_COMMISSION_FUNDS,
            'date'              => $dateTime,
        ]);

        return $query->getResult();
    }

    /**
     * @param int       $status
     * @param \DateTime $from
     *
     * @return Projects[]
     */
    public function getProjectsByStatusFromDate($status, \DateTime $from)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', $status)
            ->andWhere('p.added >= :from')
            ->setParameter('from', $from);

        return $queryBuilder->getQuery()->getResult();
    }
}
