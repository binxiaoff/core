<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class BidsRepository extends EntityRepository
{
    /**
     * @param array $criteria
     *
     * @return mixed
     */
    public function countBy(array $criteria = [])
    {
        $qb = $this->createQueryBuilder("b");
        $qb->select('COUNT(b)');
        if (false === empty($criteria)) {
            foreach ($criteria as $field => $value) {
                $qb->andWhere('b.' . $field . ' = :' . $field)
                   ->setParameter($field, $value);
            }
        }
        $query = $qb->getQuery();
        return $query->getSingleScalarResult();
    }

    /**
     * @param \DateTime   $from
     * @param \DateTime   $to
     * @param int|Clients $clientId
     *
     * @return integer
     */
    public function countByClientInPeriod(\DateTime $from, \DateTime $to, $clientId)
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.idBid) AS bidNumber')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'w.id = b.idLenderAccount')
            ->where('b.added BETWEEN :fromDate AND :toDate')
            ->andWhere('w.idClient = :idClient')
            ->setParameters(['fromDate' => $from, 'toDate' => $to, 'idClient' => $clientId]);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet    $wallet
     * @param \DateTime $date
     *
     * @return integer
     */
    public function getManualBidCountByDateAndWallet(Wallet $wallet, \DateTime $date)
    {
        $queryBuilder = $this->createQueryBuilder('b');
        $queryBuilder->select('SUM(b.idBid)')
            ->where('b.idLenderAccount = :walletId')
            ->andWhere('b.idAutobid IS NULL')
            ->andWhere('b.added > :date')
            ->setParameter('walletId', $wallet)
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet|int   $wallet
     * @param Projects|int $project
     * @param int          $status
     *
     * @return mixed
     */
    public function getSumByWalletAndProjectAndStatus($wallet, $project, $status)
    {
        $qb = $this->createQueryBuilder('b');
        $qb->select('SUM(b.amount) / 100')
            ->where('b.idProject = :project')
            ->andWhere('b.idLenderAccount = :wallet')
            ->andWhere('b.status = :status')
            ->setParameter('wallet', $wallet)
            ->setParameter('project', $project)
            ->setParameter('status', $status);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Wallet|int $wallet
     * @param int        $status
     *
     * @return mixed
     */
    public function getSumBidsForLenderAndStatus($wallet, $status)
    {
        $qb = $this->createQueryBuilder('b');
        $qb->select('ROUND(SUM(b.amount) / 100, 2)')
            ->where('b.idLenderAccount = :wallet')
            ->andWhere('b.status = :status')
            ->setParameter('wallet', $wallet)
            ->setParameter('status', $status);

        return $qb->getQuery()->getSingleScalarResult();
    }
}
