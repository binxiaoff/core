<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class ClientsRepository extends EntityRepository
{

    /**
     * @param integer|Clients $idClient
     * @return mixed
     */
    public function getLastClientStatus($idClient)
    {
        if ($idClient instanceof Clients) {
            $idClient = $idClient->getIdClient();
        }

        $cb = $this->createQueryBuilder('c');
        $cb->select('cs')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClient = csh.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatus', 'cs', Join::WITH, 'csh.idClientStatus = cs.idClientStatus')
            ->where('csh.idClient = :idClient')
            ->orderBy('csh.added', 'DESC')
            ->addOrderBy('csh.idClientStatusHistory',  'DESC')
            ->setMaxResults(1)
            ->setParameter('idClient', $idClient);
        $query  = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @param integer|Clients   $idClient
     * @param string|WalletType $walletType
     *
     * @return Clients|null
     */
    public function getWalletByType($idClient, $walletType)
    {
        if ($idClient instanceof Clients) {
            $idClient = $idClient->getIdClient();
        }

        if ($walletType instanceof WalletType) {
            $walletType = $walletType->getLabel();
        }

        $cb = $this->createQueryBuilder('c');
        $cb->select('w')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->where('w.idClient = :idClient')
            ->andWhere('wt.label = :walletType')
            ->setMaxResults(1)
            ->setParameters(['idClient' => $idClient, 'walletType' => $walletType]);
        $query = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @param integer|Clients $idClient
     * @return mixed
     */
    public function getCompany($idClient)
    {
        if ($idClient instanceof Clients) {
            $idClient = $idClient->getIdClient();
        }

        $cb = $this->createQueryBuilder('c');
        $cb->select('co')
            ->innerJoin('UnilendCoreBusinessBundle:Companies', 'co', Join::WITH, 'c.idClient = co.idClientOwner')
            ->where('c.idClient = :idClient')
            ->setParameter('idClient', $idClient);
        $query = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

    /**
     * @param integer|Clients $idClient
     * @return mixed
     */
    public function getCurrentBankAccount($idClient, $iban = null)
    {
        if ($idClient instanceof Clients) {
            $idClient = $idClient->getIdClient();
        }

        $cb = $this->createQueryBuilder('c');
        $cb->select('ba')
            ->innerJoin('UnilendCoreBusinessBundle:BankAccount', 'ba', Join::WITH, 'c.idClient = ba.idClient')
            ->where('c.idClient = :idClient')
            ->andWhere('ba.status != :status')
            ->setParameters([
                'idClient' => $idClient,
                'status'   => BankAccount::STATUS_ARCHIVED
            ]);

        if (null !== $iban) {
            $cb->andWhere('ba.iban = :iban')
                ->setParameter('iban', $iban);
        }

        $query  = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

}
