<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class ClientsRepository extends EntityRepository
{

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

}
