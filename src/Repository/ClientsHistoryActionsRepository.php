<?php

namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\ClientsHistoryActions;

class ClientsHistoryActionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientsHistoryActions::class);
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
