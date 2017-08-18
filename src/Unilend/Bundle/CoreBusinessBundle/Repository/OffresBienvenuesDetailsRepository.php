<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails;

class OffresBienvenuesDetailsRepository extends EntityRepository
{
    /**
     * @param OffresBienvenues|null $offer
     *
     * @return int
     */
    public function getSumPaidOutForOffer(OffresBienvenues $offer = null)
    {
        $queryBuilder = $this->createQueryBuilder('obd');
        $queryBuilder->select('SUM(obd.montant / 100)')
            ->where('obd.type = :type')
            ->andWhere('obd.status != :status')
            ->setParameter('type', OffresBienvenuesDetails::TYPE_OFFER)
            ->setParameter('status', OffresBienvenuesDetails::STATUS_CANCELED);

        if (null !== $offer) {
            $queryBuilder->andWhere('obd.idOffreBienvenue = :offer')
                ->setParameter('offer', $offer->getIdOffreBienvenue());
        }

        $result = $queryBuilder->getQuery()->getSingleScalarResult();
        if (null === $result) {
            return 0;
        }

        return $result;
    }

    /**
     * @param \DateTime $date
     *
     * @return array
     */
    public function findUnusedWelcomeOffers(\DateTime $date)
    {
        $date->setTime(23, 59, 59);

        $queryBuilder = $this->createQueryBuilder('obd');
        $queryBuilder->select('obd.*')
            ->innerJoin('UnilendCoreBusinessBundle:Wallet', 'w', Join::WITH, 'obd.idClient = w.idClient')
            ->where('obd.status = :unused')
            ->andWhere('obd.added <= :dateLimit')
            ->andwhere('0 = (SELECT COUNT(b.*) FROM Unilend\Bundle\CoreBusinessBundle\Entity\Bids b WHERE b.idLenderAccount = w.id AND b.status = :rejected)')
            ->setParameter('dateLimite', $date)
            ->setParameter('rejected', Bids::STATUS_BID_REJECTED);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $idClient
     * @param int $idBid
     *
     * @return mixed
     */
    public function getSumOfferByBid($idClient, $idBid)
    {
        $queryBuilder = $this->createQueryBuilder('obd');
        $queryBuilder->select('SUM(obd.montant)')
            ->where('obd.idClient = :idClient')
            ->andWhere('obd.idBid = :idBid')
            ->setParameter('idClient', $idClient)
            ->setParameter('idBid', $idBid);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
