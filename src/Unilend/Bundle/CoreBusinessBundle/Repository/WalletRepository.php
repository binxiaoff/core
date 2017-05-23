<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class WalletRepository extends EntityRepository
{

    /**
     * @return array Wallet[]
     */
    public function getTaxWallets()
    {
        $cb = $this->createQueryBuilder('w');
        $cb->select('w')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->where('wt.label IN (:taxWallets)')
            ->setParameter('taxWallets', WalletType::TAX_FR_WALLETS, Connection::PARAM_STR_ARRAY);
        $query = $cb->getQuery();

        return $query->getResult();
    }

    /**
     * @param integer|Clients   $idClient
     * @param string|WalletType $walletType
     *
     * @return Wallet|null
     */
    public function getWalletByType($idClient, $walletType)
    {
        $cb = $this->createQueryBuilder('w');
        $cb->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->where('w.idClient = :idClient')
            ->andWhere('wt.label = :walletType')
            ->setMaxResults(1)
            ->setParameters(['idClient' => $idClient, 'walletType' => $walletType]);
        $query  = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @param \DateTime  $inactiveSince
     * @param null|float $minAvailableBalance
     *
     * @return array
     */
    public function getLenderWalletWithoutOperationInPeriod(\DateTime $inactiveSince, $minAvailableBalance)
    {
        $withdrawSubQuery  = $this->getEntityManager()->createQueryBuilder()
            ->select('otw.id')
            ->from('UnilendCoreBusinessBundle:OperationType', 'otw')
            ->where('otw.label = :lenderWithdraw');
        $provisionSubQuery = $this->getEntityManager()->createQueryBuilder()
            ->select('otp.id')
            ->from('UnilendCoreBusinessBundle:OperationType', 'otp')
            ->where('otp.label = :lenderProvision');

        $queryBuilder = $this->createQueryBuilder('w');
        $queryBuilder->select('MAX(COALESCE(op.added, ow.added)) AS lastOperationDate, IDENTITY(w.idClient) AS idClient, w.availableBalance, w.id AS walletId')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'wt.id = w.idType')
            ->leftJoin('UnilendCoreBusinessBundle:Operation', 'op', Join::WITH, 'op.idWalletCreditor = w.id AND op.idType = (' . $provisionSubQuery->getDQL() . ')')
            ->leftJoin('UnilendCoreBusinessBundle:Operation', 'ow', Join::WITH, 'ow.idWalletDebtor = w.id AND ow.idType = (' . $withdrawSubQuery->getDQL() . ')')
            ->where('wt.label = :lender')
            ->setParameter('lender', WalletType::LENDER)
            ->andWhere('w.availableBalance >= :minAvailableBalance')
            ->setParameter('minAvailableBalance', $minAvailableBalance)
            ->groupBy('w.id')
            ->having('lastOperationDate <= :lastOperationDate')
            ->setParameter('lastOperationDate', $inactiveSince)
            ->setParameter('lenderWithdraw', OperationType::LENDER_WITHDRAW)
            ->setParameter('lenderProvision', OperationType::LENDER_PROVISION);

        return $queryBuilder->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
    }

    /**
     * @param \DateTime $inactiveSince
     * @param           $minAvailableBalance
     *
     * @return array
     */
    public function getLenderWalletWithoutManualBidsInPeriod(\DateTime $inactiveSince, $minAvailableBalance)
    {
        $queryBuilder = $this->createQueryBuilder('w');
        $queryBuilder->select('MAX(b.added) AS lastOperationDate, IDENTITY(w.idClient) AS idClient, w.availableBalance, w.id AS walletId')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'wt.id = w.idType')
            ->innerJoin('UnilendCoreBusinessBundle:AccountMatching', 'am', Join::WITH, 'am.idWallet = w.id')
            ->innerJoin('UnilendCoreBusinessBundle:Bids', 'b', Join::WITH, 'b.idLenderAccount = am.idLenderAccount')
            ->where('b.idAutobid IS NULL')
            ->andWhere('wt.label = :lender')
            ->setParameter('lender', WalletType::LENDER)
            ->andWhere('w.availableBalance >= :minAvailableBalance')
            ->setParameter('minAvailableBalance', $minAvailableBalance)
            ->groupBy('w.id')
            ->having('lastOperationDate <= :lastOperationDate')
            ->setParameter('lastOperationDate', $inactiveSince);

        return $queryBuilder->getQuery()->getResult(AbstractQuery::HYDRATE_SCALAR);
    }


    /**
     * @param array  $operationTypes
     * @param int    $year
     *
     * @return array Wallet[]
     */
    public function getLenderWalletsWithOperationsInYear($operationTypes, $year)
    {
        $qb = $this->createQueryBuilder('w');
        $qb->innerJoin('UnilendCoreBusinessBundle:WalletBalanceHistory', 'wbh', Join::WITH, 'w.id = wbh.idWallet')
            ->innerJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.id = wbh.idOperation')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'ot.id = o.idType')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'wt.id = w.idType')
            ->where('wt.label = :lender')
            ->andWhere('ot.label IN (:operationTypes)')
            ->andWhere('YEAR(o.added) = :year')
            ->setParameter('lender', WalletType::LENDER)
            ->setParameter('operationTypes', $operationTypes, Connection::PARAM_INT_ARRAY)
            ->setParameter('year', $year);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function getLendersWalletsWithLatePaymentsForIRR()
    {
        $now = new \DateTime('NOW');

        $qb = $this->createQueryBuilder('w')
            ->select('w')
            ->innerJoin('UnilendCoreBusinessBundle:Echeanciers', 'e', Join::WITH, 'w.id = e.idLender')
            ->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH, 'e.idProject = p.idProject')
            ->where('e.dateEcheance < :now')
            ->andWhere('e.status = 0')
            ->andWhere('p.status IN (:status)');

        $subQuery = $this->getEntityManager()->createQueryBuilder()
            ->add('select','MAX(ls.added)')
            ->add('from', 'UnilendCoreBusinessBundle:LenderStatistic ls')
            ->add('where', 'w.id = ls.idWallet');

        $qb->andWhere('(' . $subQuery->getDQL() . ') < e.dateEcheance')
            ->setParameter(':now', $now)
            ->setParameter(':status',[
                \projects_status::PROBLEME,
                \projects_status::PROBLEME_J_X,
                \projects_status::RECOUVREMENT
            ], Connection::PARAM_INT_ARRAY)
            ->groupBy('w.id');

        $query = $qb->getQuery();

        return $query->getResult();
    }
}
