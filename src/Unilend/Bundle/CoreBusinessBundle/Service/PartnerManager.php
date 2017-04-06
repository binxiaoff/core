<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class PartnerManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /**
     * @param EntityManager          $entityManager
     * @param EntityManagerSimulator $entityManagerSimulator
     */
    public function __construct(EntityManager $entityManager, EntityManagerSimulator $entityManagerSimulator)
    {
        $this->entityManager          = $entityManager;
        $this->entityManagerSimulator = $entityManagerSimulator;
    }

    /**
     * @param int  $partnerId
     * @param bool $onlyMandatory
     *
     * @return array
     */
    public function getAttachmentTypesByPartner($partnerId, $onlyMandatory = false)
    {
        /** @var \partner_project_attachment $partnerProjectAttachment */
        $partnerProjectAttachment = $this->entityManagerSimulator->getRepository('partner_project_attachment');
        $attachmentId             = [];
        $where                    = '';

        if ($onlyMandatory) {
            $where = ' AND mandatory = ' . \partner_project_attachment::MANDATORY_ATTACHMENT;
        }

        $attachmentList = $partnerProjectAttachment->select('id_partner = ' . $partnerId . $where);

        if (empty($attachmentList)) {
            return [];
        }

        foreach ($attachmentList as $attachment) {
            $attachmentId[] = $attachment['id_attachment_type'];
        }

        return $attachmentId;
    }

    /**
     * @return \partner
     */
    public function getDefaultPartner()
    {
        /** @var \partner $partner */
        $partner = $this->entityManagerSimulator->getRepository('partner');
        $partner->get(\partner::PARTNER_UNILEND_LABEL, 'label');

        return $partner;
    }

    /**
     * @param null|int $status
     * @return array
     */
    public function getPartnersSortedByName($status)
    {
        $queryBuilder = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:Partner')
            ->createQueryBuilder('p')
            ->join('p.idCompany', 'c')
            ->orderBy('c.name');

        if (null !== $status) {
            $queryBuilder
                ->where('p.status = :partnerStatus')
                ->setParameter('partnerStatus', $status);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
}
