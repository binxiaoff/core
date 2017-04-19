<?php
namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Transfer;

class TransferRepository extends EntityRepository
{
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
