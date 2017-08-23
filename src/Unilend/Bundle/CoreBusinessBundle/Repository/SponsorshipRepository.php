<?php

namespace Unilend\Bundle\CoreBusinessBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Sponsorship;
use Unilend\Bundle\CoreBusinessBundle\Entity\SponsorshipCampaign;

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

    /**
     * @param SponsorshipCampaign $campaign
     *
     * @return mixed
     */
    public function getCountSponsorByCampaign(SponsorshipCampaign $campaign)
    {
        $queryBuilder = $this->createQueryBuilder('ss');
        $queryBuilder->select('COUNT(DISTINCT ss.idClientSponsor)')
            ->where('ss.idSponsorshipCampaign = :idCampaign')
            ->setParameter('idCampaign', $campaign);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param SponsorshipCampaign $campaign
     *
     * @return mixed
     */
    public function getCountSponseeByCampaign(SponsorshipCampaign $campaign)
    {
        $queryBuilder = $this->createQueryBuilder('ss');
        $queryBuilder->select('COUNT(DISTINCT ss.idClientSponsee)')
            ->where('ss.idSponsorshipCampaign = :idCampaign')
            ->setParameter('idCampaign', $campaign);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getSponsorshipDetails()
    {
        $query = 'SELECT
                  ss.id_client_sponsee,
                  c_sponsee.prenom AS sponsee_first_name,
                  c_sponsee.nom AS sponsee_last_name,
                  c_sponsee.email AS sponsee_email,
                  o_sponsee.amount AS sponsee_amount,
                  o_sponsee.added AS sponsee_added,
                  ss.id_client_sponsor,
                  c_sponsee.source2,
                  c_sponsor.nom AS sponsor_last_name,
                  c_sponsor.prenom AS sponsor_first_name,
                  c_sponsor.email AS sponsor_email,
                  o_sponsor.amount AS sponsor_amount,
                  o_sponsor.added AS sponsor_added
                FROM sponsorship ss
                  LEFT JOIN wallet w_sponsee ON ss.id_client_sponsee = w_sponsee.id_client
                  LEFT JOIN clients c_sponsee ON ss.id_client_sponsee = c_sponsee.id_client
                  LEFT JOIN operation o_sponsee ON o_sponsee.id_wallet_creditor = w_sponsee.id AND o_sponsee.id_sponsorship = ss.id
                  LEFT JOIN wallet w_sponsor ON ss.id_client_sponsor = w_sponsor.id_client
                  LEFT JOIN clients c_sponsor ON ss.id_client_sponsor = c_sponsor.id_client
                  LEFT JOIN operation o_sponsor ON o_sponsor.id_wallet_creditor = w_sponsor.id AND o_sponsor.id_sponsorship = ss.id';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }
}
