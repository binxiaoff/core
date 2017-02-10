<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;


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
            ->executeQuery('INSERT INTO bank_account (id_client, bic, iban, date_pending, date_validated, date_archived, status, added, updated)
                            VALUES (:idClient, :bic, :iban, NOW(), NULL, NULL, :status, NOW(), NULL)
                            ON DUPLICATE KEY UPDATE status = :status, date_pending = NOW(), date_validated = NULL, date_archived = NULL, updated = NOW()',
                ['idClient' => $idClient, 'bic' => $bic, 'iban' => $iban, 'status' => BankAccount::STATUS_PENDING]);

        return $this->find($this->getEntityManager()->getConnection()->lastInsertId());
    }

    /**
     * @param Clients|integer $idClient
     *
     * @return BankAccount|null
     */
    public function getLastModifiedBankAccount($idClient)
    {
         if ($idClient instanceof Clients) {
             $idClient = $idClient->getIdClient();
         }

        $cb = $this->createQueryBuilder('ba');
        $cb->select('ba', 'COALESCE(ba.dateValidated, ba.datePending) AS dateOrder')
            ->where('ba.idClient = :idClient')
            ->andWhere('ba.status != :status')
            ->orderBy('dateOrder', 'DESC')
            ->setMaxResults(1)
            ->setParameters([
                'idClient' => $idClient,
                'status'   => BankAccount::STATUS_ARCHIVED
            ]);

        $query  = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        if (null === $result) {
            return $result;
        }

        return array_shift($result);
    }

    /**
     * @param \DateTime       $dateTime
     * @param Clients|integer $idClient
     *
     * @return Clients[]
     */
    public function getPreviousBankAccounts(\DateTime $dateTime, $idClient)
    {
        if ($idClient instanceof Clients) {
            $idClient = $idClient->getIdClient();
        }

        $cb = $this->createQueryBuilder('ba');
        $cb->select('ba')
            ->where('ba.idClient = :idClient')
            ->andWhere('ba.datePending < :dateTime')
            ->setParameters([
                'idClient' => $idClient,
                'dateTime' => $dateTime
            ]);

        return $cb->getQuery()->getResult();
    }

    /**
     * @param Clients|integer $idClient
     *
     * @return BankAccount|null
     */
    public function getGreenPointValidatedBankAccount($idClient)
    {
        if ($idClient instanceof Clients) {
            $idClient = $idClient->getIdClient();
        }

        $cb = $this->createQueryBuilder('ba');
        $cb->select('ba')
            ->innerJoin('UnilendCoreBusinessBundle:GreenpointAttachment', 'gpa', Join::WITH, 'ba.idClient = gpa.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:GreenpointAttachmentDetail', 'gpad', Join::WITH, 'gpa.idGreenpointAttachment = gpad.idGreenpointAttachment')
            ->innerJoin('UnilendCoreBusinessBundle:Attachment', 'a', Join::WITH, 'a.id = gpa.idAttachment')
            ->where('gpa.idClient = :idClient')
            ->andWhere('a.idType = :idType')
            ->andWhere('gpa.validationStatus = :status')
            ->andWhere('gpad.bankDetailsIban = ba.iban')
            ->setParameters(['idClient' => $idClient, 'idType' => \attachment_type::RIB, 'status' => 9]);

        return $cb->getQuery()->getOneOrNullResult();
    }

}
