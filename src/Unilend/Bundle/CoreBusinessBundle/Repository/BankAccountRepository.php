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
