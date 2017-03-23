<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;


class BankAccountRepository extends EntityRepository
{
    /**
     * @param Clients|integer $idClient
     *
     * @return BankAccount|null
     */
    public function getLastModifiedBankAccount($idClient)
    {
        $cb = $this->createQueryBuilder('ba');
        $cb->select('ba', 'COALESCE(ba.dateValidated, ba.datePending) AS HIDDEN dateOrder')
           ->where('ba.idClient = :idClient')
           ->andWhere('ba.dateArchived is NULL')
           ->orderBy('dateOrder', 'DESC')
           ->setMaxResults(1)
           ->setParameter('idClient', $idClient);

        return $cb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Clients|integer $idClient
     *
     * @return BankAccount|null
     */
    public function getGreenPointValidatedBankAccount($idClient)
    {
        $cb = $this->createQueryBuilder('ba');
        $cb->innerJoin('UnilendCoreBusinessBundle:GreenpointAttachment', 'gpa', Join::WITH, 'ba.idClient = gpa.idClient')
           ->innerJoin('UnilendCoreBusinessBundle:GreenpointAttachmentDetail', 'gpad', Join::WITH, 'gpa.idGreenpointAttachment = gpad.idGreenpointAttachment')
           ->innerJoin('UnilendCoreBusinessBundle:Attachment', 'a', Join::WITH, 'a.id = gpa.idAttachment')
           ->where('gpa.idClient = :idClient')
           ->andWhere('a.idType = :idType')
           ->andWhere('gpa.validationStatus = :status')
           ->andWhere('gpad.bankDetailsIban = ba.iban')
           ->setParameters(['idClient' => $idClient, 'idType' => \attachment_type::RIB, 'status' => 9]);

        return $cb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Clients|integer $idClient
     *
     * @return BankAccount|null
     */
    public function getClientValidatedBankAccount($idClient)
    {
        $qb = $this->createQueryBuilder('ba');
        $qb->where('ba.idClient = :idClient')
           ->andWhere('ba.dateValidated IS NOT NULL')
           ->andWhere('ba.dateArchived IS NULL')
           ->setParameter(':idClient', $idClient);

        return $qb->getQuery()->getOneOrNullResult();
    }

}
