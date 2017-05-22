<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class WalletCreationManager
{
    /** @var EntityManager  */
    private $entityManager;
    /** @var  LoggerInterface */
    private $logger;

    /**
     * WalletCreationManager constructor.
     * @param entityManager $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManager $entityManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * @param Clients $client
     * @param string $walletType
     */
    public function createWallet(Clients $client, $walletType)
    {
        $walletTypeRepository  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletType');
        /** @var WalletType $walletTypeEntity */
        $walletTypeEntity = $walletTypeRepository->findOneByLabel($walletType);

        switch ($walletTypeEntity->getLabel()) {
            case WalletType::LENDER :
                $wallet = $this->createBaseWallet($client, $walletTypeEntity);
                $wallet->setWireTransferPattern();
                $this->entityManager->flush($wallet);
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
        $this->entityManager->persist($wallet);
        $this->entityManager->flush($wallet);

        return $wallet;
    }
}
