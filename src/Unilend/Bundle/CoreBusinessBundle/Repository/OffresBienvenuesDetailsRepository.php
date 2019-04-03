<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Entity\{Bids, OffresBienvenues, OffresBienvenuesDetails, Wallet};

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
        $queryBuilder->select('ROUND(SUM(obd.montant / 100), 2)')
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
        $queryBuilder->innerJoin(Wallet::class, 'w', Join::WITH, 'obd.idClient = w.idClient')
            ->where('obd.status = :unused')
            ->andWhere('obd.added <= :dateLimit')
            ->andwhere('0 = (SELECT COUNT(b.idBid) FROM Unilend\Entity\Bids b WHERE b.wallet = w.id AND b.status = :rejected)')
            ->setParameter('dateLimit', $date)
            ->setParameter('unused', OffresBienvenuesDetails::STATUS_NEW)
            ->setParameter('rejected', Bids::STATUS_REJECTED);

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
