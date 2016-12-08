<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class ClientsRepository extends EntityRepository
{

    /**
     * @param $clientId
     * @return mixed
     */
    public function getLastClientStatus($clientId)
    {
        $cb = $this->createQueryBuilder('c');
        $cb->select('cs')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'c.id_client = csh.id_client')
            ->innerJoin('UnilendCoreBusinessBundle:ClientsStatus', 'cs', Join::WITH, 'csh.id_status = cs.id_status')
            ->where('csh.id_client = :clientID')
            ->orderBy('csh.added', 'DESC')
            ->addOrderBy('csh.id_client_status_history',  'DESC')
            ->setMaxResults(1)
            ->setParameter('clientID', $clientId);
        $query = $cb->getQuery();
        $result = $query->getSingleResult();

        return $result;
    }

}
