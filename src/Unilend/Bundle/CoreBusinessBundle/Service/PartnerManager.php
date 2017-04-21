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
