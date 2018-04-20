<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 27/06/2017
 * Time: 14:29
 */

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\InfolegaleExecutivePersonalChange;

class InfolegaleExecutivePersonalChangeRepository extends EntityRepository
{
    /**
     * @param string $siren
     *
     * @return array
     */
    public function getActiveExecutives(string $siren) : array
    {
        $qb = $this->createQueryBuilder('iepc');
        $qb->distinct()
            ->where('iepc.siren = :siren')
            ->andWhere('iepc.ended IS NULL')
            ->setParameter('siren', $siren);

        return $qb->getQuery()->getArrayResult();
    }


    /**
     * @param string    $siren
     * @param \DateTime $date
     *
     * @return array
     */
    public function getPreviousExecutivesLeftAfter(string $siren, \DateTime $date) : array
    {
        $currentExecutives = $this->getActiveExecutives($siren);

        $qb = $this->createQueryBuilder('iepc');
        $qb->select('iepc.idExecutive')
            ->distinct()
            ->where('iepc.siren = :siren')
            ->andWhere('iepc.ended >= :date')
            ->andWhere('iepc.idExecutive NOT IN (:currentExecutives)')
            ->setParameter('siren', $siren)
            ->setParameter('currentExecutives', $currentExecutives)
            ->setParameter('date', $date);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param string $siren
     * @param \DateTime $since
     *
     * @return array
     */
    public function getAllMandatesExceptGivenSirenOnActiveExecutives(string $siren, \DateTime $since) : array
    {
        $currentExecutives = $this->getActiveExecutives($siren);

        $queryBuilder = $this->createQueryBuilder('iepc');
        $queryBuilder->select('iepc.siren')->distinct()
            ->where('iepc.idExecutive IN (:currentExecutives)')
            ->andWhere('iepc.siren != :siren')
            ->andWhere('iepc.ended IS NULL OR iepc.ended >= :mandateEndDate')
            ->setParameter('currentExecutives', $currentExecutives)
            ->setParameter('siren', $siren)
            ->setParameter('mandateEndDate', $since);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * @param array     $executiveIds
     * @param \DateTime $mandateEndDate
     *
     * @return InfolegaleExecutivePersonalChange[]
     */
    public function findMandatesByExecutivesSince(array $executiveIds, \DateTime $mandateEndDate) : array
    {
        $queryBuilder = $this->createQueryBuilder('iepc');
        $queryBuilder->where('iepc.idExecutive IN (:executiveIds)')
            ->setParameter('executiveIds', $executiveIds)
            ->andWhere('iepc.ended IS NULL OR iepc.ended >= :mandateEndDate')
            ->setParameter('mandateEndDate', $mandateEndDate->format('Y-m-d'));

        return $queryBuilder->getQuery()->getResult();
    }
}
