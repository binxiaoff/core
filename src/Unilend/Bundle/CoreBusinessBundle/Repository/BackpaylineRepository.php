<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline;

class BackpaylineRepository extends EntityRepository
{
    /**
     * @param \DateTime $date
     * @param int       $maxTransactionFails
     * @param int       $maxCreditCards
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTransactionsToVerify(\DateTime $date, $maxTransactionFails = 3, $maxCreditCards = 3)
    {
        $binds = [
            'date'                  => $date->format('Y-m-d H:i:s'),
            'max_transaction_fails' => $maxTransactionFails,
            'max_credit_cards'      => $maxCreditCards
        ];
        $query = '
        SELECT
          c.id_client,
          b.id_wallet,
          GROUP_CONCAT(b.id)            AS idTransactionList,
          (SELECT COUNT(DISTINCT b_wallet.id_backpayline)
           FROM backpayline b_wallet
           WHERE b_wallet.amount > 0 AND b_wallet.code != \'00000\' AND b.id_wallet = b_wallet.id_wallet AND b_wallet.added >= b.added
           GROUP BY b_wallet.id_wallet) AS nb_transactions,
          (SELECT COUNT(DISTINCT b_card.card_number)
           FROM backpayline b_card
           WHERE b_card.amount > 0 AND b_card.code != \'00000\' AND b.id_wallet = b_card.id_wallet AND b_card.added >= b.added
           GROUP BY b_card.id_wallet)   AS nb_cards
        FROM backpayline b
          INNER JOIN wallet w ON w.id = b.id_wallet
          INNER JOIN clients c ON c.id_client = w.id_client
        WHERE b.added >= :date AND b.id_wallet IS NOT NULL
        GROUP BY c.id_client
        HAVING nb_transactions >= :max_transaction_fails AND nb_cards >= :max_credit_cards
        ';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, $binds)->fetchAll();
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCountFailedTransactionsBetweenDates(\DateTime $start, \DateTime $end)
    {
        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $queryBuilder = $this->createQueryBuilder('bp');
        $queryBuilder->select('COUNT(bp.idBackpayline)')
            ->where('bp.code NOT IN (:codes)')
            ->andWhere('bp.added BETWEEN :start AND :end')
            ->andWhere('bp.cardNumber IS NOT NULL')
            ->setParameter('codes', [Backpayline::CODE_TRANSACTION_APPROVED, Backpayline::CODE_TRANSACTION_CANCELLED])
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'));

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return Backpayline[]
     */
    public function findPaylineTransactionsToApprove()
    {
        $queryBuilder = $this->createQueryBuilder('bp');
        $queryBuilder->where('bp.code IS NULL')
            ->andWhere('bp.token IS NOT NULL');

        return $queryBuilder->getQuery()->getResult();
    }
}
