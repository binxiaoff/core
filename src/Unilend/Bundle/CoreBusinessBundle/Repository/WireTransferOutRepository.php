<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;

class WireTransferOutRepository extends EntityRepository
{
    /**
     * @param           $status
     * @param \DateTime $dateTime
     *
     * @return array
     */
    public function findWireTransferBefore($status, \DateTime $dateTime)
    {
        $qb = $this->createQueryBuilder('wto');
        $qb->where('wto.status = :status')
           ->andWhere('wto.added <= :added')
           ->setParameter('status', $status)
           ->setParameter('added', $dateTime);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param integer|Clients $client
     * @param array           $status
     *
     * @return Clients[]
     */
    public function findWireTransferToThirdParty($client, array $status)
    {
        $qb = $this->createQueryBuilder('wto');
        $qb->innerJoin('UnilendCoreBusinessBundle:BankAccount', 'ba', Join::WITH, 'wto.bankAccount = ba.id')
           ->where('ba.idClient != wto.idClient')
           ->andWhere('wto.idClient = :client')
           ->andWhere('wto.status in (:status)')
           ->orderBy('wto.added', 'DESC')
           ->setParameter('client', $client)
           ->setParameter('status', $status, Connection::PARAM_INT_ARRAY);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Virements[]
     */
    public function findWireTransferReadyToSend()
    {
        $qb = $this->createQueryBuilder('wto');
        $qb->where('wto.status = :ready')
            ->andWhere('wto.addedXml IS NULL')
            ->andWhere('wto.transferAt IS NULL OR wto.transferAt <= :today')
            ->setParameter('ready', Virements::STATUS_VALIDATED)
            ->setParameter('today', new \DateTime());

        return $qb->getQuery()->getResult();
    }
}
