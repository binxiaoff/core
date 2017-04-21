<?php


namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\CoreBusinessBundle\Entity\AccountMatching;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class WalletCreationManager
{
    /** @var EntityManager  */
    private $em;
    /** @var  EntityManagerSimulator */
    private $entityManager;
    /** @var  LoggerInterface */
    private $logger;

    /**
     * WalletCreationManager constructor.
     * @param EntityManager $em
     * @param EntityManagerSimulator $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManager $em,
        EntityManagerSimulator $entityManager,
        LoggerInterface $logger
    ) {
        $this->em            = $em;
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * @param Clients $client
     * @param string $walletType
     */
    public function createWallet(Clients $client, $walletType)
    {
        $walletTypeRepository  = $this->em->getRepository('UnilendCoreBusinessBundle:WalletType');
        /** @var WalletType $walletTypeEntity */
        $walletTypeEntity = $walletTypeRepository->findOneByLabel($walletType);

        switch ($walletTypeEntity->getLabel()) {
            case WalletType::LENDER :
                $wallet = $this->createBaseWallet($client, $walletTypeEntity);
                $wallet->setWireTransferPattern();
                $this->em->flush($wallet);
                $this->createLegacyLenderAccount($client, $wallet);
                break;
            case WalletType::BORROWER:
                $this->createBaseWallet($client, $walletTypeEntity);
                break;
            default:
                $this->logger->info('Unknown wallet type ', [['class' => __CLASS__, 'function' => __FUNCTION__]]);
                break;
        }
    }

    /**
     * @param Clients    $client
     * @param WalletType $walletType
     *
     * @return Wallet
     */
    private function createBaseWallet(Clients $client, WalletType $walletType)
    {
        $wallet = new Wallet();
        $wallet->setIdClient($client);
        $wallet->setIdType($walletType);
        $wallet->setAvailableBalance(0);
        $this->em->persist($wallet);
        $this->em->flush($wallet);

        return $wallet;
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
        $companyEntity = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getCompany($client->getIdClient());
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
        $this->em->flush($accountMatching);
    }
}
