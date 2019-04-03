<?php

namespace Unilend\Repository;

use Doctrine\ORM\EntityRepository;
use Unilend\Entity\{Clients, OperationSubType, Sponsorship, SponsorshipCampaign};

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
                      INNER JOIN sponsorship_campaign ON sponsorship.id_campaign = sponsorship_campaign.id
                      INNER JOIN operation ON operation.id_sponsorship = sponsorship.id
                      INNER JOIN operation_sub_type ON operation.id_sub_type = operation_sub_type.id AND label = :subTypeLabel
                    WHERE sponsorship.status != ' . Sponsorship::STATUS_ONGOING . '
                      AND DATEDIFF(DATE_ADD(operation.added, INTERVAL sponsorship_campaign.validity_days DAY), CURDATE()) <= 0
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
     * @param SponsorshipCampaign|null $campaign
     *
     * @return mixed
     */
    public function getCountSponsorByCampaign(SponsorshipCampaign $campaign = null)
    {
        $queryBuilder = $this->createQueryBuilder('ss');
        $queryBuilder->select('COUNT(DISTINCT ss.idClientSponsor)');

        if (null !== $campaign) {
            $queryBuilder->where('ss.idCampaign = :idCampaign')
                ->setParameter('idCampaign', $campaign);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param SponsorshipCampaign $campaign
     *
     * @return mixed
     */
    public function getCountSponseeByCampaign(SponsorshipCampaign $campaign = null)
    {
        $queryBuilder = $this->createQueryBuilder('ss');
        $queryBuilder->select('COUNT(DISTINCT ss.idClientSponsee)');

        if (null !== $campaign) {
            $queryBuilder->where('ss.idCampaign = :idCampaign')
                ->setParameter('idCampaign', $campaign);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getPaidOutSponsorshipDetails()
    {
        $query = 'SELECT
                  ss.id_client_sponsee,
                  c_sponsee.prenom AS sponsee_first_name,
                  c_sponsee.nom AS sponsee_last_name,
                  c_sponsee.email AS sponsee_email,
                  o_sponsee.amount AS sponsee_amount,
                  o_sponsee.added AS sponsee_added,
                  ss.id_client_sponsor,
                  c_sponsor.sponsor_code AS sponsor_code,
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
                  LEFT JOIN operation o_sponsor ON o_sponsor.id_wallet_creditor = w_sponsor.id AND o_sponsor.id_sponsorship = ss.id 
                  WHERE o_sponsee.added IS NOT NULL OR o_sponsor.added IS NOT NULL';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query)
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param Clients $client
     *
     * @return array
     */
    public function getSponsorshipDetailBySponsee(Clients $client)
    {
        $query = $query = $this->getSponsorshipDetailQuery() . ' WHERE ss.id_client_sponsee = :idClient';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['idClient' => $client->getIdClient()])
            ->fetchAll(\PDO::FETCH_ASSOC);

    }

    /**
     * @param Clients $client
     *
     * @return array
     */
    public function getSponsorshipDetailBySponsor(Clients $client)
    {
        $query = $this->getSponsorshipDetailQuery() .
            ' WHERE ss.id_client_sponsor = :idClient';

        return $this->getEntityManager()
            ->getConnection()
            ->executeQuery($query, ['idClient' => $client->getIdClient()])
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getSponsorshipDetailQuery()
    {
        return 'SELECT
                  ss.id AS id_sponsorship,
                  ss.id_client_sponsee,
                  c_sponsee.prenom AS sponsee_first_name,
                  c_sponsee.nom AS sponsee_last_name,
                  c_sponsee.email AS sponsee_email,
                  IF(ss.status = ' . Sponsorship::STATUS_ONGOING . ', "false", "true") AS sponsee_reward_paid,
                  ss.id_client_sponsor,
                  c_sponsee.source2,
                  c_sponsor.nom AS sponsor_last_name,
                  c_sponsor.prenom AS sponsor_first_name,
                  c_sponsor.email AS sponsor_email,
                  IF(ss.status = ' . Sponsorship::STATUS_SPONSOR_PAID . ', "true", "false") AS sponsor_reward_paid
                FROM sponsorship ss
                  LEFT JOIN clients c_sponsee ON ss.id_client_sponsee = c_sponsee.id_client
                  LEFT JOIN clients c_sponsor ON ss.id_client_sponsor = c_sponsor.id_client';
    }
}
