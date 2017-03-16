<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class BackpaylineRepository extends EntityRepository
{
    /**
     * @param \DateTime $date
     * @param int       $maxTransactionFails
     * @param int       $maxCreditCards
     * @return array
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
}
