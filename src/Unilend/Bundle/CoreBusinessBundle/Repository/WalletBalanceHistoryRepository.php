<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
use function var_dump;

class WalletBalanceHistoryRepository extends EntityRepository
{
    /**
     * @param  Wallet|integer $wallet
     * @param \DateTime       $date
     *
     * @return null|WalletBalanceHistory
     */
    public function getBalanceOfTheDay($wallet, \DateTime $date)
    {
        if ($wallet instanceof Wallet) {
            $wallet = $wallet->getId();
        }

        $date->setTime('23', '59', '59');

        $qb = $this->createQueryBuilder('w');
        $qb->andWhere('w.idWallet = :wallet')
           ->andWhere('w.added <= :dateTime')
           ->setParameters(['wallet' => $wallet, 'dateTime' => $date])
           ->orderBy('w.added', 'DESC')
           ->addOrderBy('w.id', 'DESC')
           ->setMaxResults(1);
        $query = $qb->getQuery();

        return $query->getOneOrNullResult();
    }

    /**
     * @param int         $idWallet
     * @param \DateTime   $startDate
     * @param \DateTime   $endDate
     * @param array       $idProjects
     * @param string|null $operationType
     *
     * @return array
     */
    public function getBorrowerWalletOperations($idWallet, \DateTime $startDate, \DateTime $endDate, array $idProjects, $operationType = null)
    {
        $startDate->setTime('00', '00', '00');
        $endDate->setTime('23', '59', '59');

        $qb = $this->createQueryBuilder('wbh');
        $qb->select('
        o.id,
        CASE WHEN(o.idWalletDebtor= wbh.idWallet) THEN -SUM(o.amount) ELSE SUM(o.amount) AS amount, 
        ot.label, 
        IDENTITY(o.idProject) AS idProject, 
        IDENTITY(o.idPaymentSchedule) AS idPaymentSchedule, 
        DATE(o.added) AS date,
        ROUND(f.montantHt/100, 2) AS netCommission,
        ROUND(f.tva/100, 2) AS vat,
        e.ordre')
            ->innerJoin('UnilendCoreBusinessBundle:Operation', 'o', Join::WITH, 'o.id = wbh.idOperation')
            ->innerJoin('UnilendCoreBusinessBundle:OperationType', 'ot', Join::WITH, 'o.idType = ot.id')
            ->leftJoin('UnilendCoreBusinessBundle:EcheanciersEmprunteur', 'ee', Join::WITH, 'o.idPaymentSchedule = ee.idEcheancierEmprunteur')
            ->leftJoin('UnilendCoreBusinessBundle:Factures', 'f', Join::WITH, 'ee.ordre = f.ordre AND ee.idProject = f.idProject')
            ->leftJoin('UnilendCoreBusinessBundle:Echeanciers', 'e', Join::WITH, 'o.idRepaymentSchedule = e.idEcheancier')
            ->where('wbh.idWallet = :idWallet')
            ->andWhere('o.idProject IN (:idProjects)')
            ->andWhere('o.added BETWEEN :startDate AND :endDate')
            ->setParameter('idWallet', $idWallet)
            ->setParameter('idProjects', $idProjects, Connection::PARAM_STR_ARRAY)
            ->setParameter('startDate', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('endDate', $endDate->format('Y-m-d H:i:s'))
            ->groupBy('o.idProject, o.idType, date')
            ->orderBy('wbh.id', 'DESC');

        if (null !== $operationType) {
            $qb->andWhere('ot.label = :operationType')
                ->setParameter('operationType', $operationType);
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }
}
