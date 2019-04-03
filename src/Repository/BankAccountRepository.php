<?php

namespace Unilend\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Entity\{BankAccount, Clients};

class BankAccountRepository extends EntityRepository
{
    /**
     * @param Clients|integer $idClient
     *
     * @return BankAccount|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLastModifiedBankAccount($idClient): ?BankAccount
    {
        $cb = $this->createQueryBuilder('ba');
        $cb->select('ba', 'COALESCE(ba.updated, ba.datePending) AS HIDDEN dateOrder')
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
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getClientValidatedBankAccount($idClient): ?BankAccount
    {
        $qb = $this->createQueryBuilder('ba');
        $qb->where('ba.idClient = :idClient')
            ->andWhere('ba.dateValidated IS NOT NULL')
            ->andWhere('ba.dateArchived IS NULL')
            ->orderBy('ba.dateValidated', 'DESC')
            ->setMaxResults(1)
            ->setParameter(':idClient', $idClient);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
