<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class DebtCollectionMissionRepository extends EntityRepository
{
    /**
     * @param array $missionId
     *
     * @return mixed
     */
    public function getEntrustedAmount(array $missionId)
    {
        $queryBuilder = $this->createQueryBuilder('dcm')
            ->select('SUM(ee.capital + ee.interets + ee.commission + ee.tva) AS entrustedAmount')
            ->innerJoin('UnilendCoreBusinessBundle:DebtCollectionMissionPaymentSchedule', 'dcmps', Join::WITH, 'dcmps.idMission = dcm.id')
            ->innerJoin('UnilendCoreBusinessBundle:EcheanciersEmprunteur', 'ee', Join::WITH, 'ee.idEcheancierEmprunteur = dcmps.idPaymentSchedule')
            ->where('dcm.id IN (:missionId)')
            ->setParameter('missionId', $missionId)
            ->groupBy('ee.idProject');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}