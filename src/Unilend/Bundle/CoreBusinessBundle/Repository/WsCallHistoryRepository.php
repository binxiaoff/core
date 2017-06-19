<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\WsExternalResource;

class WsCallHistoryRepository extends EntityRepository
{
    /**
     * @param WsExternalResource $wsResource
     * @param \DateTime          $from
     * @param bool               $onlyGivenResource
     *
     * @return array
     */
    public function getCallStatusHistoryFromDate(WsExternalResource $wsResource, \DateTime $from, $onlyGivenResource = false)
    {

        $firstDownQb = $this->createQueryBuilder('wchMin')
            ->select('MIN(wchMin.added)')
            ->where('wchMin.idResource = wer.idResource')
            ->andWhere('wchMin.callStatus = :statusError')
            ->andWhere('wchMin.added >= :minDate');

        $queryBuilder = $this->createQueryBuilder('wch')
            ->select('
            wer.idResource, wer.label, wer.providerName,
            SUM(CASE WHEN wch.callStatus = :valid THEN 1 ELSE 0 END) nbValidCalls,
            SUM(CASE WHEN wch.callStatus = :warning THEN 1 ELSE 0 END) nbWarningCalls,
            SUM(CASE WHEN wch.callStatus = :error THEN 1 ELSE 0 END) nbErrorCalls,
            COUNT(1) AS totalByResource,' .
                '(' . $firstDownQb->getDQL() . ') AS firstErrorDate'
            )
            ->innerJoin('UnilendCoreBusinessBundle:WsExternalResource', 'wer', Join::WITH, 'wer.idResource = wch.idResource')
            ->where('wch.added >= :from')
            ->setParameter('from', $from)
            ->andWhere('wer.providerName = :provider')
            ->setParameter('provider', $wsResource->getProviderName())
            ->setParameter('valid', 'valid')
            ->setParameter('warning', 'warning')
            ->setParameter('error', 'error')
            ->setParameter('statusError', 'error')
            ->setParameter('minDate', $from);

        if ($onlyGivenResource) {
            $queryBuilder->andWhere('wer.idResource = :idResource')
                ->setParameter('idResource', $wsResource);
        }
        $queryBuilder->groupBy('wer.idResource');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param WsExternalResource $wsExternalResource
     * @param \DateTime          $from
     *
     * @return null|\DateTime
     */
    public function getFirstUpCallFromDate(WsExternalResource $wsExternalResource, \DateTime $from)
    {
        $lastDownQB = $this->createQueryBuilder('lastDown')
            ->select('MAX(lastDown.added)')
            ->where('lastDown.idResource = :resource')
            ->andWhere('lastDown.callStatus = :error')
            ->andWhere('lastDown.added < :from');


        $queryBuilder = $this->createQueryBuilder('wchMin')
            ->select('MIN(wchMin.added) AS firstUpCallDate')
            ->where('wchMin.idResource = :resourceId')
            ->setParameter('resourceId', $wsExternalResource)
            ->andWhere('wchMin.callStatus IN (:callStatus)')
            ->setParameter('callStatus', ['valid', 'warning'], Connection::PARAM_STR_ARRAY)
            ->andWhere('wchMin.added > (' . $lastDownQB->getDQL() . ')')
            ->setParameter('resource', $wsExternalResource)
            ->setParameter('error', 'error')
            ->setParameter('from', $from);

        return $queryBuilder->getQuery()->getSingleResult();
    }
}
