<?php


namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\LendersAccounts;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class WalletCreationManager
{
    /** @var EntityManager  */
    private $em;
    /** @var LenderManager */
    private $lenderManager;
    /** @var  \Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager */
    private $entityManager;

    public function __construct(EntityManager $em, LenderManager $lenderManager, \Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager $entityManager)
    {
        $this->em = $em;
        $this->lenderManager = $lenderManager;
        $this->entityManager = $entityManager;
        //TODO add to services.xml
    }

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

    private function createLenderWallet(Clients $client, WalletType $walletType)
    {
        $this->lenderManager->saveLenderTermsOfUse($client);

        $wallet = new Wallet();
        $wallet->setIdClient($client);
        $wallet->setIdType($walletType);
        $wallet->setAvailableBalance(0);
        $this->em->persist($wallet);

        /** @var \lenders_accounts $lendersAccount */
        $lendersAccount = $this->entityManager->getRepository('lenders_accounts');
        $lendersAccount->id_client_owner = $client->getIdClient();
        $lendersAccount->create();
    }
}
