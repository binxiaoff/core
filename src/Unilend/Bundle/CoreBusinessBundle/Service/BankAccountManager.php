<?php


namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccountUsage;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccountUsageType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Repository\ClientsRepository;
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class BankAccountManager
{
    /** @var  EntityManager */
    private $em;
    /** @var  EntityManagerSimulator */
    private $entityManager;
    /** @var  LenderManager */
    private $lenderManager;
    /** @var  LoggerInterface */
    private $logger;

    public function __construct(
        EntityManager $em,
        EntityManagerSimulator $entityManager,
        LenderManager $lenderManager,
        LoggerInterface $logger
    ) {
        $this->em            = $em;
        $this->entityManager = $entityManager;
        $this->lenderManager = $lenderManager;
        $this->logger        = $logger;
    }


    /**
     * @param Clients $clientEntity
     * @param string $bic
     * @param string $iban
     *
     * @return BankAccount
     */
    public function saveBankInformation(Clients $clientEntity, $bic, $iban)
    {
        /** @var ClientsRepository $clientsRepository */
        $clientsRepository = $this->em->getRepository('UnilendCoreBusinessBundle:Clients');
        $bankAccount = $clientsRepository->getBankAccount($clientEntity->getIdClient(), $iban);

        if (null === $bankAccount) {
            $bankAccount = new BankAccount();
            $bankAccount->setIdClient($clientEntity);
            $bankAccount->setIban($iban);
            $bankAccount->setBic($bic);
            $this->em->persist($bankAccount);
        } else {
            if ($bankAccount->getBic() !== $bic) {
                $bankAccount->setBic($bic);
            }
        }

        $this->em->flush();
        $this->updateLegacyBankAccount($clientEntity, $bankAccount);

        return $bankAccount;
    }

    /**
     * @param BankAccount $bankAccount
     * @param string $bankAccountUsageType
     */
    public function createBankAccountUsage(BankAccount $bankAccount, $bankAccountUsageType)
    {
        /** @var ClientsRepository $clientsRepository */
        $clientsRepository = $this->em->getRepository('UnilendCoreBusinessBundle:Clients');
        $walletType = '';

        switch ($bankAccountUsageType){
            case BankAccountUsageType::LENDER_DEFAULT:
                $walletType = WalletType::LENDER;
                break;
            case BankAccountUsageType::BORROWER_DEFAULT:
                $walletType = WalletType::BORROWER;
                break;
            default:
                $this->logger->warning('Unknown bankAccountUsage : ' . $bankAccountUsageType, [['class' => __CLASS__, 'function' => __FUNCTION__, 'id_client' => $bankAccount->getIdClient()]]);
                break;
        }
        /** @var Wallet $wallet */
        $wallet =  $clientsRepository->getWalletByType($bankAccount->getIdClient()->getIdClient(), $walletType);

        $bankAccountUsageTypeEntity = $this->em->getRepository('UnilendCoreBusinessBundle:BankAccountUsageType')->findOneByLabel($bankAccountUsageType);
        $bankAccountUsage           = new BankAccountUsage();
        $bankAccountUsage->setIdUsageType($bankAccountUsageTypeEntity);
        $bankAccountUsage->setIdWallet($wallet);
        $bankAccountUsage->setIdBankAccount($bankAccount);
        $this->em->persist($bankAccountUsage);
        $this->em->flush();
    }

    /**
     * @param Clients $client
     * @param BankAccount $bankAccount
     */
    private function updateLegacyBankAccount(Clients $client, BankAccount $bankAccount)
    {
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManager->getRepository('lenders_accounts');
        $lenderAccount->get($client->getIdClient(), 'id_client_owner');
        $lenderAccount->bic   = $bankAccount->getBic();
        $lenderAccount->iban  = $bankAccount->getIban();
        $lenderAccount->motif = $this->lenderManager->getLenderPattern($client);
        $lenderAccount->update();
    }
}
