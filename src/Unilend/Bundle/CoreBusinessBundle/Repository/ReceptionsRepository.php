<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectRepaymentTask;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;

class ReceptionsRepository extends EntityRepository
{
    /**
     * @param \DateTime $date
     *
     * @return array
     */
    public function getByDate(\DateTime $date)
    {
        $from = new \DateTime($date->format("Y-m-d") . " 00:00:00");
        $to   = new \DateTime($date->format("Y-m-d") . " 23:59:59");

        $queryBuilder = $this->createQueryBuilder("r");
        $queryBuilder->andWhere('r.added BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get all assigned the direct debts or wire transfers to borrowers
     *
     * @param int   $limit
     * @param int   $offset
     * @param array $sorts
     * @param array $search
     *
     * @return Receptions[]
     */
    public function getBorrowerAttributions($limit = null, $offset = null, array $sorts = [], array $search = [])
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->where('r.idProject IS NOT NULL');

        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        if (null !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }

        if (false === empty($sorts)) {
            foreach ($sorts as $sort => $order) {
                $queryBuilder->addOrderBy('r.' . $sort, $order);
            }
        }

        if (false === empty($search)) {
            $orClause = [];
            foreach ($search as $column => $value) {
                $orClause[] = $queryBuilder->expr()->eq('r.' . $column, $value);
            }
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$orClause));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array $search
     *
     * @return int
     */
    public function getBorrowerAttributionsCount(array $search = [])
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->select('count(r)')
            ->andWhere('r.idProject IS NOT NULL');

        if (false === empty($search)) {
            $orClause = [];
            foreach ($search as $column => $value) {
                $orClause[] = $queryBuilder->expr()->eq('r.' . $column, $value);
            }
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$orClause));
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all assigned the direct debts or wire transfers to lenders
     *
     * @param int   $limit
     * @param int   $offset
     * @param array $sorts
     * @param array $search
     *
     * @return Receptions[]
     */
    public function getLenderAttributions($limit = null, $offset = null, array $sorts = [], array $search = [])
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.idClient IS NOT NULL')
            ->andWhere('r.idProject IS NULL')
            ->andWhere('r.type = :directDebit AND r.statusPrelevement = :directDebitSent OR r.type = :wireTransfer AND r.statusVirement = :wireTransferReceived')
            ->setParameter('directDebit', Receptions::TYPE_DIRECT_DEBIT)
            ->setParameter('directDebitSent', Receptions::DIRECT_DEBIT_STATUS_SENT)
            ->setParameter('wireTransfer', Receptions::TYPE_WIRE_TRANSFER)
            ->setParameter('wireTransferReceived', Receptions::WIRE_TRANSFER_STATUS_RECEIVED);

        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        if (null !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }

        if (false === empty($sorts)) {
            foreach ($sorts as $sort => $order) {
                $queryBuilder->addOrderBy('r.' . $sort, $order);
            }
        }

        if (false === empty($search)) {
            $orClause = [];
            foreach ($search as $column => $value) {
                $orClause[] = $queryBuilder->expr()->eq('r.' . $column, $value);
            }
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$orClause));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param array $search
     *
     * @return int
     */
    public function getLenderAttributionsCount(array $search = [])
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->select('count(r)')
            ->andWhere('r.idClient IS NOT NULL')
            ->andWhere('r.idProject IS NULL')
            ->andWhere('r.type = :directDebit AND r.statusPrelevement = :directDebitSent OR r.type = :wireTransfer AND r.statusVirement = :wireTransferReceived')
            ->setParameter('directDebit', Receptions::TYPE_DIRECT_DEBIT)
            ->setParameter('directDebitSent', Receptions::DIRECT_DEBIT_STATUS_SENT)
            ->setParameter('wireTransfer', Receptions::TYPE_WIRE_TRANSFER)
            ->setParameter('wireTransferReceived', Receptions::WIRE_TRANSFER_STATUS_RECEIVED);

        if (false === empty($search)) {
            $orClause = [];
            foreach ($search as $column => $value) {
                $orClause[] = $queryBuilder->expr()->eq('r.' . $column, $value);
            }
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$orClause));
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array $search
     *
     * @return int
     */
    public function getNonAttributionsCount(array $search = [])
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->select('count(r)')
            ->andWhere('r.statusBo = :pending')
            ->setParameter('pending', Receptions::STATUS_PENDING);

        if (false === empty($search)) {
            $orClause = [];
            foreach ($search as $column => $value) {
                $orClause[] = $queryBuilder->expr()->eq('r.' . $column, $value);
            }
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$orClause));
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int   $limit
     * @param int   $offset
     * @param array $sorts
     * @param array $search
     *
     * @return Receptions[]
     */
    public function getNonAttributions($limit = null, $offset = null, array $sorts = [], array $search = [])
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->andWhere('r.statusBo = :pending')
            ->setParameter('pending', Receptions::STATUS_PENDING);

        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        if (null !== $offset) {
            $queryBuilder->setFirstResult($offset);
        }

        if (false === empty($sorts)) {
            foreach ($sorts as $sort => $order) {
                $queryBuilder->addOrderBy('r.' . $sort, $order);
            }
        }

        if (false === empty($search)) {
            $orClause = [];
            foreach ($search as $column => $value) {
                $orClause[] = $queryBuilder->expr()->eq('r.' . $column, $value);
            }
            $queryBuilder->andWhere($queryBuilder->expr()->orX(...$orClause));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Projects $project
     *
     * @return Receptions[]
     */
    public function getBorrowerAnticipatedRepaymentWireTransfer(Projects $project)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->where('r.idProject = :projectId')
            ->andWhere('r.statusBo IN (:assignmentType)')
            ->andWhere('r.typeRemb = :earlyRepayment')
            ->andWhere('r.type = :wireTransfer')
            ->andWhere('r.statusVirement = :wireTransferReceived')
            ->setParameter('projectId', $project)
            ->setParameter('assignmentType', [Receptions::STATUS_ASSIGNED_MANUAL, Receptions::STATUS_ASSIGNED_AUTO], Connection::PARAM_INT_ARRAY)
            ->setParameter('earlyRepayment', Receptions::REPAYMENT_TYPE_EARLY)
            ->setParameter('wireTransfer', Receptions::TYPE_WIRE_TRANSFER)
            ->setParameter('wireTransferReceived', Receptions::WIRE_TRANSFER_STATUS_RECEIVED);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function getRejectedDirectDebitIndicatorsBetweenDates(\DateTime $start, \DateTime $end)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->select('COUNT(r.idReception) AS number')
            ->addSelect('ROUND(SUM(r.montant) / 100, 2) AS amount')
            ->where('r.type = :directDebit')
            ->andWhere('r.statusPrelevement = :rejected')
            ->andWhere('r.added BETWEEN :start AND :end')
            ->setParameter('directDebit', Receptions::TYPE_DIRECT_DEBIT)
            ->setParameter('rejected', Receptions::DIRECT_DEBIT_STATUS_REJECTED)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        return $queryBuilder->getQuery()->getArrayResult()[0];
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return mixed
     */
    public function getBorrowerProvisionRegularizationIndicatorsBetweenDates(\DateTime $start, \DateTime $end)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->select('COUNT(r.idReception) AS number')
            ->addSelect('ROUND(SUM(r.montant) / 100, 2) AS amount')
            ->where('r.type != :directDebit')
            ->andWhere('r.typeRemb = :regularization')
            ->andWhere('r.added BETWEEN :start AND :end')
            ->setParameter('directDebit', Receptions::TYPE_DIRECT_DEBIT)
            ->setParameter('regularization', Receptions::REPAYMENT_TYPE_REGULARISATION)
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        return $queryBuilder->getQuery()->getArrayResult()[0];
    }

    /**
     * @param Projects|int $project
     * @param \DateTime    $from
     *
     * @return mixed
     */
    public function findOriginalDirectDebitByRejectedOne($project, \DateTime $from)
    {
        $queryBuilder = $this->createQueryBuilder('r');
        $queryBuilder->where('r.idProject = :project')
            ->andWhere('r.statusBo in (:affected)')
            ->andWhere('r.statusPrelevement = :sent')
            ->andWhere('r.type = :directDebit')
            ->andWhere('r.added >= :from')
            ->setParameter('project', $project)
            ->setParameter('affected', [Receptions::STATUS_ASSIGNED_AUTO, Receptions::STATUS_ASSIGNED_MANUAL])
            ->setParameter('sent', Receptions::DIRECT_DEBIT_STATUS_SENT)
            ->setParameter('directDebit', Receptions::TYPE_DIRECT_DEBIT)
            ->setParameter('from', $from)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Get all wire transfer in flux that didn't have a repayment task yet
     *
     * @param Projects|int $project
     *
     * @return Receptions[]
     */
    public function findPendingWireTransferIn($project)
    {
        return $this->buildPendingWireTransferInQuery($project)->getQuery()->getResult();
    }

    /**
     * @param Projects|int $project
     *
     * @return float
     */
    public function getTotalPendingWireTransferIn($project)
    {
        $queryBuilder = $this->buildPendingWireTransferInQuery($project);

        $queryBuilder->select('ROUND(SUM(IFNULL(r.montant, 0))/100, 2)');
        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Projects|int $project
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function buildPendingWireTransferInQuery($project)
    {
        $qbRejected = $this->createQueryBuilder('r_rejected')
            ->select('r_rejected.idReception')
            ->where('r_rejected.idReceptionRejected = r.idReception');

        $qbTreatedReception = $this->createQueryBuilder('r_treated')
            ->select('IDENTITY(prt.idWireTransferIn)')
            ->from('UnilendCoreBusinessBundle:ProjectRepaymentTask', 'prt')
            ->where('prt.idProject = :projectId')
            ->andWhere('prt.status != :cancelled')
            ->andWhere('prt.idWireTransferIn = r.idReception');

        $queryBuilder = $this->createQueryBuilder('r')
            ->where('r.idProject = :projectId')
            ->setParameter('projectId', $project)
            ->andWhere('r.statusPrelevement != :directDebitRejected')
            ->setParameter('directDebitRejected', Receptions::DIRECT_DEBIT_STATUS_REJECTED)
            ->andWhere('r.statusVirement != :wireTransferRejected')
            ->setParameter('wireTransferRejected', Receptions::WIRE_TRANSFER_STATUS_REJECTED)
            ->andWhere('NOT EXISTS (' . $qbRejected->getDQL() . ')')
            ->andWhere('NOT EXISTS (' . $qbTreatedReception->getDQL() . ')')
            ->setParameter('cancelled', ProjectRepaymentTask::STATUS_CANCELLED);

        return $queryBuilder;
    }

    /**
     * Get all receipts having a pending repayment task
     *
     * @return Receptions[]
     */
    public function findReceptionsWithPendingRepaymentTasks()
    {
        $qbRejected = $this->createQueryBuilder('r_rejected')
            ->select('r_rejected.idReception')
            ->where('r_rejected.idReceptionRejected = r.idReception');

        $queryBuilder = $this->createQueryBuilder('r')
            ->innerJoin('UnilendCoreBusinessBundle:ProjectRepaymentTask', 'prt', Join::WITH, 'prt.idWireTransferIn = r.idReception')
            ->where('prt.status = :pendingTask')
            ->setParameter('pendingTask', ProjectRepaymentTask::STATUS_PENDING)
            ->andWhere('NOT EXISTS (' . $qbRejected->getDQL() . ')')
            ->groupBy('r.idReception');

        return $queryBuilder->getQuery()->getResult();
    }
}

