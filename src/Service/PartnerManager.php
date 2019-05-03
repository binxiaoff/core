<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\{BankAccount, Clients, Companies, CompanyClient, Partner};

class PartnerManager
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return Partner
     */
    public function getDefaultPartner()
    {
        return $this->entityManager
            ->getRepository(Partner::class)
            ->findOneBy(['label' => Partner::PARTNER_CALS_LABEL])
        ;
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
            $bankAccount = $this->entityManager->getRepository(BankAccount::class)->getClientValidatedBankAccount($client);
            if ($bankAccount) {
                $bankAccounts[] = $bankAccount;
            }
        }

        return $bankAccounts;
    }

    /**
     * @param Clients $client
     *
     * @return Partner|null
     */
    public function getPartner(Clients $client): ?Partner
    {
        if (false === $client->isPartner() || empty($client->getCompany())) {
            return null;
        }

        $rootCompany = $client->getCompany();

        while ($rootCompany->getParent() && $rootCompany->getParent()->getIdCompany()) {
            $rootCompany = $rootCompany->getParent();
        }

        return $this->entityManager->getRepository(Partner::class)->findOneBy(['idCompany' => $rootCompany->getIdCompany()]);
    }

    /**
     * @param Clients $partnerUser
     *
     * @return Companies[]
     */
    public function getUserCompanies(Clients $partnerUser): array
    {
        /** @var CompanyClient $partnerRole */
        $partnerRole = $this->entityManager->getRepository(CompanyClient::class)->findOneBy(['idClient' => $partnerUser]);
        $company     = $partnerRole->getIdCompany();
        $branches    = [];

        if (in_array(Clients::ROLE_PARTNER, $partnerUser->getRoles())) {
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
        $branches = $this->entityManager->getRepository(Companies::class)->findBy(['idParentCompany' => $rootCompany]);

        foreach ($branches as $company) {
            $branches = array_merge($branches, $this->getBranches($company));
        }

        return $branches;
    }
}
