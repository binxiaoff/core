<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class PartnerManager
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * PartnerManager constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int  $partnerId
     * @param bool $onlyMandatory
     * @return array
     */
    public function getAttachmentTypesByPartner($partnerId, $onlyMandatory = false)
    {
        /** @var \partner_project_attachment $partnerProjectAttachment */
        $partnerProjectAttachment = $this->entityManager->getRepository('partner_project_attachment');
        $attachmentId             = [];
        $where                    = '';

        if ($onlyMandatory) {
            $where = ' AND mandatory = ' . \partner_project_attachment::MANDATORY_ATTACHMENT;
        }

        $attachmentList = $partnerProjectAttachment->select('id_partner = ' . $partnerId . $where);

        if (empty($attachmentList)) {
            return [];
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
