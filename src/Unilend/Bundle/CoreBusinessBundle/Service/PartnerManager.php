<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class PartnerManager
{
    /** @var EntityManager */
    private $entityManager;

    static $mandatoryAttachmentTypes = [
        \attachment_type::DERNIERE_LIASSE_FISCAL,
        \attachment_type::LIASSE_FISCAL_N_1,
        \attachment_type::LIASSE_FISCAL_N_2,
        \attachment_type::RELEVE_BANCAIRE_MOIS_N,
        \attachment_type::RELEVE_BANCAIRE_MOIS_N_1,
        \attachment_type::RELEVE_BANCAIRE_MOIS_N_2,
        \attachment_type::KBIS,
        \attachment_type::RIB,
        \attachment_type::CNI_PASSPORTE_DIRIGEANT,
        \attachment_type::ETAT_PRIVILEGES_NANTISSEMENTS,
        \attachment_type::CGV
    ];

    /**
     * PartnerManager constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $partnerId
     * @return array
     */
    public function getAttachmentTypesByPartner($partnerId)
    {
        /** @var \partner_project_attachment $partnerProjectAttachment */
        $partnerProjectAttachment = $this->entityManager->getRepository('partner_project_attachment');
        $attachmentId             = [];

        if (empty($partnerId) || empty($attachmentList = $partnerProjectAttachment->select('id_partner = ' . $partnerId))) {
            return self::$mandatoryAttachmentTypes;
        } else {
            foreach ($attachmentList as $attachment) {
                $attachmentId[] = $attachment['id_attachment_type'];
            }

            return $attachmentId;
        }
    }

    /**
     * @return \partner
     */
    public function getDefaultPartner()
    {
        /** @var \partner $partner */
        $partner = $this->entityManager->getRepository('partner');
        $partner->get(\partner::PARTNER_UNILEND_LABEL, 'label');

        return $partner;
    }
}