<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 27/06/2017
 * Time: 14:29
 */

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;

class InfolegaleExecutivePersonalChangeRepository extends EntityRepository
{
    /**
     * @param string $siren
     *
     * @return array
     */
    public function getActiveExecutives($siren)
    {
        $qb = $this->createQueryBuilder('iepc');
        $qb->select('DISTINCT iepc.idExecutive')
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
    public function getPreviousExecutivesLeftAfter($siren, \DateTime $date)
    {
        $currentExecutives = $this->getActiveExecutives($siren);

        $qb = $this->createQueryBuilder('iepc');
        $qb->select('DISTINCT iepc.idExecutive')
            ->where('iepc.siren = :siren')
            ->andWhere('iepc.ended >= :date')
            ->andWhere('iepc.idExecutive NOT IN (:currentExecutives)')
            ->setParameter('siren', $siren)
            ->setParameter('currentExecutives', $currentExecutives)
            ->setParameter('date', $date);

        return $qb->getQuery()->getArrayResult();
    }
}
