<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, Wallet, WalletType};

class WalletCreationManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * @param Clients $client
     * @param string  $walletType
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createWallet(Clients $client, string $walletType)
    {
        $walletTypeRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletType');
        $walletTypeEntity     = $walletTypeRepository->findOneBy(['label' => $walletType]);

        switch ($walletTypeEntity->getLabel()) {
            case WalletType::LENDER:
                $wallet = $this->createBaseWallet($client, $walletTypeEntity);
                $wallet->setWireTransferPattern();
                $this->entityManager->flush($wallet);
                break;
            case WalletType::BORROWER:
            case WalletType::PARTNER:
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
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createBaseWallet(Clients $client, WalletType $walletType)
    {
        $wallet = new Wallet();
        $wallet->setIdClient($client);
        $wallet->setIdType($walletType);
        $wallet->setAvailableBalance(0);
        $wallet->setCommittedBalance(0);

        $this->entityManager->persist($wallet);
        $this->entityManager->flush($wallet);

        return $wallet;
    }
}
