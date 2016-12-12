<?php


namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\AccountMatching;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class WalletCreationManager
{
    /** @var EntityManager  */
    private $em;
    /** @var  \Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager */
    private $entityManager;

    public function __construct(
        EntityManager $em,
        \Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager $entityManager
    ) {
        $this->em = $em;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Clients $client
     * @param $walletType
     */
    public function createWallet(Clients $client, $walletType)
    {
        $walletTypeRepository  = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType');
        /** @var WalletType $walletTypeEntity */
        $walletTypeEntity = $walletTypeRepository->findOneByLabel($walletType);

        switch($walletTypeEntity->getLabel()){
             case WalletType::LENDER :
                 $this->createLenderWallet($client, $walletTypeEntity);
                break;
        }
    }

    /**
     * @param Clients $client
     * @param WalletType $walletType
     */
    private function createLenderWallet(Clients $client, WalletType $walletType)
    {
        $wallet = new Wallet();
        $wallet->setIdClient($client);
        $wallet->setIdType($walletType);
        $wallet->setAvailableBalance(0);
        $this->em->persist($wallet);

        $this->createLegacyLenderAccount($client, $wallet);
    }

    /**
     * @param Clients $client
     * @param Wallet $wallet
     */
    private function createLegacyLenderAccount(Clients $client, Wallet $wallet)
    {
        /** @var \lenders_accounts $lendersAccount */
        $lendersAccount = $this->entityManager->getRepository('lenders_accounts');
        /** @var Companies $companyEntity */
        $companyEntity = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getClientCompany($client->getIdClient());
        if (is_object($companyEntity)) {
            $lendersAccount->id_company_owner = $companyEntity->getIdCompany();
        }

        $lendersAccount->id_client_owner = $client->getIdClient();
        $lendersAccount->status           = \lenders_accounts::LENDER_STATUS_ONLINE;
        $lendersAccount->create();

        $lendersAccountEntity = $this->em->getRepository('UnilendCoreBusinessBundle:LendersAccounts')->find($lendersAccount->id_lender_account);
        $accountMatching = new AccountMatching();
        $accountMatching->setIdLenderAccount($lendersAccountEntity);
        $accountMatching->setIdWallet($wallet);
        $this->em->persist($accountMatching);
        $this->em->flush();
    }
}
