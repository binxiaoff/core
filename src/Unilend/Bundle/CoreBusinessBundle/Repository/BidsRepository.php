<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use \Doctrine\ORM\Query\Expr\Join;
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
     * @param \DateTime $from
     * @param \DateTime $to
     * @param int       $clientId
     *
     * @return mixed
     */
    public function countByClientInPeriod(\DateTime $from, \DateTime $to, $clientId)
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.idBid) AS bidNumber')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'w.id = b.idLenderAccount')
            ->where('b.added BETWEEN :fromDate AND :toDate')
            ->andWhere('w.idClient = :idClient')
            ->setParameters(['fromDate' => $from, 'toDate' => $to, 'idClient' => $clientId]);

        $bidCount =  $qb->getQuery()->getScalarResult();

        return $bidCount;
    }

    /**
     * @param Wallet    $wallet
     * @param \DateTime $date
     * @return mixed
     */
    public function getManualBidByDateAndWallet(Wallet $wallet, \DateTime $date)
    {
        $queryBuilder = $this->createQueryBuilder('b');
        $queryBuilder->innerJoin('UnilendCoreBusinessBundle:AccountMatching', 'am', Join::WITH, 'am.idWallet = :walletId')
            ->where('b.idLenderAccount = am.idLenderAccount')
            ->andWhere('b.idAutobid IS NULL')
            ->andWhere('b.added > :date')
            ->setParameter('walletId', $wallet->getId())
            ->setParameter('date', $date);

        return $queryBuilder->getQuery()->getOneOrNullResult();
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
}
