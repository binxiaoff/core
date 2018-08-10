<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\CompanyClient;
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

    /**
     * @param Clients $client
     *
     * @return null|Partner
     */
    public function getPartner(Clients $client): ?Partner
    {
        if (false === $client->isPartner() || $client->getCompanyClient()) {
            return null;
        }

        $rootCompany = $client->getCompanyClient()->getIdCompany();

        while ($rootCompany->getIdParentCompany() && $rootCompany->getIdParentCompany()->getIdCompany()) {
            $rootCompany = $rootCompany->getIdParentCompany();
        }

        return $this->entityManager->getRepository('UnilendCoreBusinessBundle:Partner')->findOneBy(['idCompany' => $rootCompany->getIdCompany()]);
    }

    /**
     * @param Clients $partnerUser
     *
     * @return Companies[]
     */
    public function getUserCompanies(Clients $partnerUser): array
    {
        /** @var CompanyClient $partnerRole */
        $partnerRole = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyClient')->findOneBy(['idClient' => $partnerUser]);
        $company     = $partnerRole->getIdCompany();
        $branches    = [];

        if (in_array(Clients::ROLE_PARTNER_ADMIN, $partnerUser->getRoles())) {
            $branches = $this->getBranches($company);
        }

        $companies = array_merge([$company], $branches);

        usort($companies, function ($first, $second) {
            return strcasecmp($first->getName(), $second->getName());
        });

        return $companies;
    }

    /**
     * @param Companies $rootCompany
     *
     * @return Companies[]
     */
    private function getBranches(Companies $rootCompany): array
    {
        $branches = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->findBy(['idParentCompany' => $rootCompany]);

        foreach($branches as $company) {
            $branches = array_merge($branches, $this->getBranches($company));
        }

        return $branches;
    }
}
