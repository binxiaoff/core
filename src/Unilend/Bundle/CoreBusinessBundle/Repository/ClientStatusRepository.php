<?php


namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class ClientStatusRepository extends EntityRepository
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

        $cb = $this->createQueryBuilder('cs');
        $cb->innerJoin('UnilendCoreBusinessBundle:ClientsStatusHistory', 'csh', Join::WITH, 'csh.idClientStatus = cs.idClientStatus')
            ->where('csh.idClient = :idClient')
            ->orderBy('csh.added', 'DESC')
            ->addOrderBy('csh.idClientStatusHistory',  'DESC')
            ->setMaxResults(1)
            ->setParameter('idClient', $idClient);
        $query  = $cb->getQuery();
        $result = $query->getOneOrNullResult();

        return $result;
    }

}