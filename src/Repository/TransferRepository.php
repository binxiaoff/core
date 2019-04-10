<?php
namespace Unilend\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Unilend\Entity\Transfer;

class TransferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transfer::class);
    }

    /**
     * @param $client
     *
     * @return Transfer[]
     */
    public function findTransferByClient($client)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->where($qb->expr()->eq('t.idClientOrigin', ':client'))
           ->orWhere($qb->expr()->eq('t.idClientReceiver', ':client'))
           ->setParameter('client', $client);

        return $qb->getQuery()->getResult();
    }
}
