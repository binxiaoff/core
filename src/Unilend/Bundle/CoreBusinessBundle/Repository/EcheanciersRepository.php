<?php


namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\FrontBundle\Controller\LenderDashboardController;
use function var_dump;

class EcheanciersRepository extends EntityRepository
{

    public function getLostCapitalForLender($idLender)
    {
        $projectStatusCollectiveProceeding = [
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE,
            \projects_status::DEFAUT
        ];

        $qb = $this->createQueryBuilder('e');
        $qb->select('SUM(e.capital)')
            ->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH, 'e.idProject = p.idProject')
            ->where('e.idLender = :idLender')
            ->andWhere('e.status = ' . \echeanciers::STATUS_PENDING)
            ->andWhere('p.status IN (:projectStatus) OR (p.status = ' . \projects_status::RECOUVREMENT . ' AND DATEDIFF(NOW(), e.dateEcheance) > 180)')
            ->setParameter('idLender', $idLender)
            ->setParameter('projectStatus', $projectStatusCollectiveProceeding, Connection::PARAM_INT_ARRAY);

        $amount = $qb->getQuery()->getSingleScalarResult();

        return $amount;
    }

    /**
     * @param int $idLender
     * @param string $timeFrame
     *
     * @return string
     * @throws \Exception
     */
    public function getMaxRepaymentAmountForLender($idLender, $timeFrame)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('ROUND(SUM((e.capital + e.interets) / 100), 2) AS amount')
            ->where('e.idLender = :idLender')
            ->orderBy('amount', 'DESC')
            ->groupBy('timeFrame')
            ->setMaxResults(1)
            ->setParameter('idLender', $idLender);

        switch ($timeFrame) {
            case LenderDashboardController::REPAYMENT_TIME_FRAME_MONTH :
                $qb->addSelect('LPAD(e.dateEcheance, 7, \' \' ) AS timeFrame');
                break;
            case LenderDashboardController::REPAYMENT_TIME_FRAME_QUARTER:
                $qb->addSelect('QUARTER(e.dateEcheance) AS timeFrame');
                break;
            case LenderDashboardController::REPAYMENT_TIME_FRAME_YEAR:
                $qb->addSelect('YEAR(e.dateEcheance) AS timeFrame');
                break;
            default:
                throw new \Exception('Time frame is not supported, see LenderDashboardController for possibilities');
                break;
        }

        $result = $qb->getQuery()->getResult();
        if (empty($result)) {
            return 0;
        }

        return $result[0]['amount'];
    }

}
