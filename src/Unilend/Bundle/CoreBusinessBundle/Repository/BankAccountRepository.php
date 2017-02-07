<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;


class BankAccountRepository extends EntityRepository
{
    /**
     * @param int    $idClient
     * @param string $bic
     * @param string $iban
     *
     * @return BankAccount
     */
    public function saveBankAccount($idClient, $bic, $iban)
    {
        $this->getEntityManager()
            ->getConnection()
            ->executeQuery('INSERT INTO bank_account (id_client, bic, iban, status, added, updated) 
                            VALUES (:idClient, :bic, :iban, :status, NOW(), null)
                            ON DUPLICATE KEY UPDATE status = :status, updated = NOW()', ['idClient' => $idClient, 'bic' => $bic, 'iban' => $iban, 'status' => BankAccount::STATUS_PENDING]);

        return $this->find($this->getEntityManager()->getConnection()->lastInsertId());
    }
}
