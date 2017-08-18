<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;
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
}
