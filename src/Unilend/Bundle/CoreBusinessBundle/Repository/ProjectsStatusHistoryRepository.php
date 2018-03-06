<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatusHistory;

class ProjectsStatusHistoryRepository extends EntityRepository
{
    /**
     * @param int|Projects       $project
     * @param int|ProjectsStatus $status
     *
     * @return ProjectsStatusHistory|null
     */
    public function findStatusFirstOccurrence($project, $status)
    {
        if ($project instanceof Projects) {
            $project = $project->getIdProject();
        }
        if ($status instanceof ProjectsStatus) {
            $status = $status->getStatus();
        }
        $qb = $this->createQueryBuilder('psh');
        $qb->innerJoin('UnilendCoreBusinessBundle:ProjectsStatus', 'ps', Join::WITH, 'ps.idProjectStatus = psh.idProjectStatus')
            ->andWhere('psh.idProject = :project')
            ->andWhere('ps.status = :status')
            ->setParameter('project', $project)
            ->setParameter('status', $status)
            ->orderBy('psh.added', 'ASC')
            ->setMaxResults(1);
        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param int|Projects $project
     * @param int|array    $status
     *
     * @return ProjectsStatusHistory|null
     */
    public function findStatusLastOccurrence($project, $status)
    {
        if (false === is_array($status)) {
            $status = [$status];
        }
        if ($project instanceof Projects) {
            $project = $project->getIdProject();
        }

        $qb = $this->createQueryBuilder('psh');
        $qb->innerJoin('UnilendCoreBusinessBundle:ProjectsStatus', 'ps', Join::WITH, 'ps.idProjectStatus = psh.idProjectStatus')
            ->andWhere('psh.idProject = :project')
            ->andWhere('ps.status in  (:status)')
            ->setParameter('project', $project)
            ->setParameter('status', $status)
            ->orderBy('psh.added', 'DESC')
            ->setMaxResults(1);
        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param DateTime $dateAdded
     * @param array    $projectStatus
     *
     * @return array|bool
     */
    public function getProjectStatusChangesOnDate(\DateTime $dateAdded, $projectStatus)
    {
        if (empty($dateAdded)) {
            return false;
        }

        if (empty($projectStatus) || false === is_array($projectStatus)) {
            return false;
        }

        $qb = $this->createQueryBuilder('psh');
        $qb->innerJoin('UnilendCoreBusinessBundle:ProjectsStatus', 'ps', Join::WITH, 'ps.idProjectStatus = psh.idProjectStatus')
            ->andWhere('DATE(psh.added) = :date')
            ->andWhere('ps.status IN (:status)')
            ->setParameter(':date', $dateAdded->format('Y-m-d'))
            ->setParameter(':status', $projectStatus, Connection::PARAM_INT_ARRAY);
        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     *
     * @return int
     */
    public function getCountProjectsInRiskReviewBetweenDates(\DateTime $start, \DateTime $end)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $query = 'SELECT COUNT(p.id_project) FROM projects p
                      INNER JOIN projects_status_history psh ON psh.id_project = p.id_project
                      INNER JOIN (SELECT
                                    MAX(id_project_status_history) AS max_id_project_status_history
                                  FROM projects_status_history psh_max
                                    INNER JOIN projects_status ps_max ON psh_max.id_project_status = ps_max.id_project_status
                                  WHERE ps_max.status >= ' . ProjectsStatus::COMITY_REVIEW . ' AND ps_max.status <=  ' . ProjectsStatus::A_FUNDER . '
                                  GROUP BY id_project) t ON t.max_id_project_status_history = psh.id_project_status_history
                      INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                    WHERE psh.added BETWEEN :start AND :end';

        $result = $this->getEntityManager()->getConnection()->executeQuery($query, [
                'start' => $start->format('Y-m-d H:i:s'),
                'end'   => $end->format('Y-m-d H:i:s')
            ])->fetchColumn();

        return $result;
    }

    /**
     * @param int      $status
     * @param DateTime $started
     * @param DateTime $ended
     *
     * @return array
     */
    public function getStatusByDates(int $status, \DateTime $started, \DateTime $ended): array
    {
        $queryBuilder = $this->createQueryBuilder('psh');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:ProjectsStatus', 'ps', Join::WITH, 'ps.idProjectStatus = psh.idProjectStatus')
            ->where('ps.status = :status')
            ->andWhere('psh.added >= :started')
            ->andWhere('psh.added <= :ended')
            ->groupBy('psh.idProject, ps.status')
            ->setParameters(['status' => $status, 'started' => $started, 'ended' => $ended]);

        return $queryBuilder->getQuery()->getArrayResult();
    }


    /**
     * @param string   $contractType
     * @param DateTime $date
     *
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCountProjectInProblemWithContractOnDate(string $contractType, \DateTime $date)
    {
        $query = '
            SELECT
              COUNT(DISTINCT psh2.id_project)
            FROM (
                   SELECT id_project, MAX(id_project_status_history) AS max_psh
                   FROM projects_status_history psh
                   GROUP BY id_project
                 ) AS t
              INNER JOIN projects_status_history psh2 ON t.max_psh
              INNER JOIN projects_status ps ON ps.id_project_status = psh2.id_project_status
              INNER JOIN (SELECT DISTINCT id_project, id_type_contract FROM loans l) l_distinct ON psh2.id_project = l_distinct.id_project
              INNER JOIN underlying_contract c ON l_distinct.id_type_contract = c.id_contract
            WHERE c.label = :contractType
            AND ps.status = :problem
            AND psh2.added <= :date';

        $result = $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['problem' => ProjectsStatus::PROBLEME, 'contractType' => $contractType, 'date' => $date->format('Y-m-d H:i:s')])
            ->fetchColumn();

        return $result;
    }

    /**
     * @param string   $contractType
     * @param DateTime $date
     *
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCountProjectInRepaymentWithContractOnDate(string $contractType, \DateTime $date)
    {
        $query = '
            SELECT
              COUNT(DISTINCT psh2.id_project)
            FROM (
                   SELECT id_project, MAX(id_project_status_history) AS max_psh
                   FROM projects_status_history psh
                    GROUP BY id_project
                 ) AS t
              INNER JOIN projects_status_history psh2 ON t.max_psh
              INNER JOIN projects_status ps ON psh2.id_project_status = ps.id_project_status
              INNER JOIN (SELECT DISTINCT id_project, id_type_contract FROM loans l) l_distinct ON psh2.id_project = l_distinct.id_project
              INNER JOIN underlying_contract c ON l_distinct.id_type_contract = c.id_contract
            WHERE ps.status >= :repayment AND ps.status NOT IN (:repaid)
                  AND c.label = :contractType
                  AND psh2.added <= :date';

        $result = $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['repayment'    => ProjectsStatus::REMBOURSEMENT,
                                    'repaid'       => [ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE],
                                    'contractType' => $contractType,
                                    'date'         => $date->format('Y-m-d H:i:s')
            ], ['repayment'    => \PDO::PARAM_INT,
                'repaid'       => Connection::PARAM_INT_ARRAY,
                'contractType' => \PDO::PARAM_STR,
                'date'         => \PDO::PARAM_STR
            ])->fetchColumn();

        return $result;
    }
}
