<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

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
          INNER JOIN clients c ON c.id_client = la.id_client_owner
          WHERE lih.id_lender = :id_lender';

        return $this->getEntityManager()->getConnection()
            ->executeQuery($sql,
                ['id_lender' => $lenderId],
                ['id_lender' => \PDO::PARAM_INT]
            )->fetch(\PDO::FETCH_ASSOC);
    }
}
