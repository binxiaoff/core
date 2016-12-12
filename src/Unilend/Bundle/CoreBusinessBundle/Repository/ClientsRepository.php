<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class ClientsRepository extends EntityRepository
{

    /**
     * @param integer $idClient
     * @return mixed
     */
    public function getLastClientStatus($idClient)
    {
        $cb = $this->createQueryBuilder('c');
        $cb->select('cs')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.idClient = csh.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatus', 'cs', Join::WITH, 'csh.idStatus = cs.idStatus')
            ->where('csh.idClient = :idClient')
            ->orderBy('csh.added', 'DESC')
            ->addOrderBy('csh.idClientStatusHistory',  'DESC')
            ->setMaxResults(1)
            ->setParameter('idClient', $idClient);
        $query = $cb->getQuery();
        $result = $query->getSingleResult();

        return $result;
    }

    /**
     * @param integer $idClient
     * @param string $walletType
     * @return mixed
     */
    public function getClientWalletByType($idClient, $walletType)
    {
        $cb = $this->createQueryBuilder('c');
        $cb->select('w')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'c.idClient = w.idClient')
            ->innerJoin('UnilendCoreBusinessBundle:WalletType', 'wt', Join::WITH, 'w.idType = wt.id')
            ->where('w.idClient = :idClient')
            ->andWhere('wt.label = :walletType')
            ->setMaxResults(1)
            ->setParameters(['idClient' => $idClient, 'walletType' => $walletType]);
        $query = $cb->getQuery();
        $result = $query->getSingleResult();

        return $result;
    }

    /**
     * @param integer $idClient
     * @return mixed
     */
    public function getClientCompany($idClient)
    {
        $cb = $this->createQueryBuilder('c');
        $cb->select('co')
            ->innerJoin('UnilendCoreBusinessBundle:Companies', 'co', Join::WITH, 'c.idClient = co.idClientOwner')
            ->where('c.idClient = :idClient')
            ->setParameter('idClient', $idClient);
        $query = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

}
