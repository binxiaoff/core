<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Partner;

class PartnerManager
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return Partner
     */
    public function getDefaultPartner()
    {
        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->findOneBy(['label' => Partner::PARTNER_UNILEND_LABEL]);
    }

    /**
     * @param Partner $partner
     *
     * @return BankAccount[]
     */
    public function getPartnerThirdPartyBankAccounts(Partner $partner)
    {
        $bankAccounts = [];
        $thirdParties = $partner->getPartnerThirdParties();
        foreach ($thirdParties as $thirdParty) {
            $client      = $thirdParty->getIdCompany()->getIdClientOwner();
            $bankAccount = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($client);
            if ($bankAccount) {
                $bankAccounts[] = $bankAccount;
            }
        }

        return $bankAccounts;
    }
}
