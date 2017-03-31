<?php


namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

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
        $qb->select('SUM(e.capital')
            ->innerJoin('UnilendCoreBusinessBundle:Projects', 'p', Join::WITH, 'e.idProject = p.idProject')
            ->where('e.idLender = :idLender')
            ->andWhere('e.status = ' . \echeanciers::STATUS_PENDING)
            ->andWhere('p.status IN (:projectStatus) OR (p.status = ' . \projects_status::RECOUVREMENT . ' AND DATEDIFF(NOW(), e.date_echeance) > 180)')
            ->setParameter('idLender', $idLender)
            ->setParameter('projectStatus', $projectStatusCollectiveProceeding, Connection::PARAM_INT_ARRAY);

        $amount = $qb->getQuery()->getSingleScalarResult();
        return $amount;

//        $query = '
//            SELECT SUM(e.capital)
//            FROM echeanciers e
//            INNER JOIN projects p ON e.id_project = p.id_project
//            WHERE
//                e.id_lender = ' . $iLendersAccountId . '
//                AND e.status = 0
//                AND (p.status IN (' . implode(',', $aProjectStatusCollectiveProceeding) . ')
//                    OR (p.status = ' . \projects_status::RECOUVREMENT . ' AND DATEDIFF(NOW(), e.date_echeance) > 180))';

        $result               = $this->bdd->query($sql);
        $fRemainingDueCapital = ($this->bdd->result($result, 0, 0) / 100);
    }

}