<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatusHistory;

class ClientsStatusHistoryRepository extends EntityRepository
{
    /**
     * @param integer|Clients $idClient
     *
     * @return ClientsStatusHistory
     */
    public function getFirstClientValidation($idClient)
    {
        if ($idClient instanceof Clients) {
            $idClient = $idClient->getIdClient();
        }

        $cb = $this->createQueryBuilder('csh');
        $cb->innerJoin('UnilendCoreBusinessBundle:ClientsStatus', 'cs', Join::WITH, 'csh.idClientStatus = cs.idClientStatus')
            ->where('csh.idClient = :idClient')
            ->andWhere('cs.status = :status')
            ->orderBy('csh.added', 'DESC')
            ->addOrderBy('csh.idClientStatusHistory',  'DESC')
            ->setMaxResults(1)
            ->setParameter('idClient', $idClient)
            ->setParameter('status', ClientsStatus::VALIDATED);
        $query  = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }
}
