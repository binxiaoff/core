<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsHistoryActions;

class ClientsHistoryActionsRepository extends EntityRepository
{
    /**
     * @param int $clientId
     *
     * @return array
     */
    public function getLastAutoBidOnOffActions($clientId)
    {
        $qb = $this->createQueryBuilder('cha');
        $qb->where('cha.nomForm = :autobid')
            ->andWhere('cha.idClient = :idClient')
            ->orderBy('cha.added', 'DESC')
            ->setMaxResults(2)
            ->setParameter('idClient', $clientId)
            ->setParameter('autobid', ClientsHistoryActions::AUTOBID_SWITCH);
        $query  = $qb->getQuery();

        return $query->getArrayResult();
    }

    /**
     * @param int $clientId
     *
     * @return mixed
     */
    public function countAutobidActivationHistory($clientId)
    {
        $qb = $this->createQueryBuilder('cha');
        $qb->select('COUNT(cha.idClientHistoryAction)')
            ->where('cha.nomForm = :autobid')
            ->andWhere('cha.idClient = :idClient')
            ->setParameter('idClient', $clientId)
            ->setParameter('autobid', ClientsHistoryActions::AUTOBID_SWITCH);
        $query  = $qb->getQuery();

        return $query->getScalarResult();
    }

}
