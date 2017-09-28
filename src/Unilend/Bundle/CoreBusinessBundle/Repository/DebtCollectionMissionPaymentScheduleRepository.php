<?php


namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;

class DebtCollectionMissionPaymentScheduleRepository extends EntityRepository
{
    /**
     * @param array $mission
     *
     * @return mixed
     */
    public function getEntrustedAmount(array $mission)
    {
        $queryBuilder = $this->createQueryBuilder('dcmps')
            ->select('SUM(dcmps.capital + dcmps.interest + dcmps.commissionVatIncl) AS entrustedAmount')
            ->where('dcmps.idMission IN (:missionId)')
            ->setParameter('missionId', $mission);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}