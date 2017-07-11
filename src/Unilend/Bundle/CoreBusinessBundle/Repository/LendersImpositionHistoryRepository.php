<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class LendersImpositionHistoryRepository extends EntityRepository
{
    /**
     * @param int $lenderId
     *
     * @return array
     */
    public function getLenderTypeAndFiscalResidence($lenderId)
    {
        $sql = '
          SELECT
              MAX(id_lenders_imposition_history) AS id_lenders_imposition_history,
              CASE IFNULL(resident_etranger, 0)
                WHEN 0
                  THEN "fr"
                  ELSE "ww"
              END AS fiscal_address,
              CASE c.type
                WHEN ' . Clients::TYPE_LEGAL_ENTITY . ' THEN "legal_entity" 
                WHEN ' . Clients::TYPE_LEGAL_ENTITY_FOREIGNER . ' THEN "legal_entity" 
                WHEN ' . Clients::TYPE_PERSON .  ' THEN "person"
                WHEN ' . Clients::TYPE_PERSON_FOREIGNER . ' THEN "person"
              END AS client_type
          FROM lenders_imposition_history lih
          INNER JOIN wallet w ON w.id = lih.id_lender
          INNER JOIN clients c ON c.id_client = w.id_client
          WHERE lih.id_lender = :id_lender';

        return $this->getEntityManager()->getConnection()
            ->executeQuery($sql,
                ['id_lender' => $lenderId],
                ['id_lender' => \PDO::PARAM_INT]
            )->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param Wallet    $wallet
     * @param \DateTime $date
     *
     * @return mixed
     */
    public function getFiscalIsoAtDate(Wallet $wallet, \DateTime $date)
    {
        $date->setTime(23, 59, 59);

        $queryBuilder = $this->createQueryBuilder('lih');
        $queryBuilder->select('p.iso')
            ->innerJoin('UnilendCoreBusinessBundle:PaysV2', 'p', Join::WITH, 'p.idPays = lih.idPays')
            ->where('lih.idLender = :wallet')
            ->andWhere('lih.added <= :date')
            ->orderBy('lih.added', 'DESC')
            ->setMaxResults(1)
            ->setParameter('wallet', $wallet->getId())
            ->setParameter('date', $date->format('Y-m-d H:i:s'));

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
