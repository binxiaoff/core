<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Sponsorship;

class SponsorshipRepository extends EntityRepository
{
    /**
     * @return array
     */
    public function findExpiredSponsorshipsSponsee()
    {
        return $this->findExpiredSponsorships(OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSEE, OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_SPONSORSHIP_REWARD_SPONSEE);
    }

    /**
     * @return array
     */
    public function findExpiredSponsorshipsSponsor()
    {
        return $this->findExpiredSponsorships(OperationSubType::UNILEND_PROMOTIONAL_OPERATION_SPONSORSHIP_REWARD_SPONSOR, OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_SPONSORSHIP_REWARD_SPONSOR);
    }

    /**
     * @param string $subType
     * @param string $subTypeCancel
     *
     * @return array
     */
    private function findExpiredSponsorships($subType, $subTypeCancel)
    {
        $query = '  SELECT
                      sponsorship.*
                    FROM sponsorship
                      INNER JOIN sponsorship_campaign ON sponsorship.id_sponsorship_campaign = sponsorship_campaign.id
                      INNER JOIN operation ON operation.id_sponsorship = sponsorship.id
                      INNER JOIN operation_sub_type ON operation.id_sub_type = operation_sub_type.id AND label = :subTypeLabel
                    WHERE sponsorship.status != ' . Sponsorship::STATUS_ONGOING . '
                      AND DATEDIFF(DATE_ADD(operation.added, INTERVAL sponsorship_campaign.validity_days DAY), CURDATE()) < 0
                      AND NOT EXISTS (
                                      SELECT * FROM operation 
                                      INNER JOIN operation_sub_type ON operation.id_sub_type = operation_sub_type.id 
                                      WHERE label = :subTypeCancelLabel AND id_sponsorship = sponsorship_campaign.id)';

        $result = $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['subTypeLabel' => $subType, 'subTypeCancelLabel' => $subTypeCancel])
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }
}
