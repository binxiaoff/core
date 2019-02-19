<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, ClientsStatus, WalletType};

class ClientCreationManager
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var WalletCreationManager */
    private $walletCreationManager;
    /** @var ClientStatusManager */
    private $clientStatusManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param WalletCreationManager  $walletCreationManager
     * @param ClientStatusManager    $clientStatusManager
     * @param LoggerInterface        $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        WalletCreationManager $walletCreationManager,
        ClientStatusManager $clientStatusManager,
        LoggerInterface $logger
    )
    {
        $this->entityManager         = $entityManager;
        $this->walletCreationManager = $walletCreationManager;
        $this->clientStatusManager   = $clientStatusManager;
        $this->logger                = $logger;
    }

    /**
     * @param Clients  $client
     * @param string   $walletType
     * @param int      $userId
     * @param int|null $status
     */
    public function createAccount(Clients $client, string $walletType, int $userId, ?int $status = null): void
    {
        if (false === in_array($walletType, [WalletType::LENDER, WalletType::BORROWER, WalletType::PARTNER])) {
            $this->logger->error('Account creation is not possible for wallet type "' . $walletType . '"', [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__
            ]);
            return;
        }

        if (null === $status) {
            switch ($walletType) {
                case WalletType::LENDER:
                    $status = ClientsStatus::STATUS_CREATION;
                    break;
                case WalletType::BORROWER:
                case WalletType::PARTNER:
                    $status = ClientsStatus::STATUS_VALIDATED;
                    break;
            }
        }

        $this->entityManager->getConnection()->beginTransaction();

        try {
            $this->clientStatusManager->addClientStatus($client, $userId, $status);
            $this->walletCreationManager->createWallet($client, $walletType);

            $this->entityManager->getConnection()->commit();

            /** In case this method is called in another transaction,
             * and client is not refreshed after its creation further use of the client does not work properly.
             * The methods isLender/isBorrower/isPartner return false even if the client has a wallet of the correct type */
            $this->entityManager->refresh($client);
        } catch (\Exception $exception) {
            $this->logger->error('Error while creating client account. Message: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);

            try {
                $this->entityManager->getConnection()->rollBack();
            } catch (ConnectionException $rollBackException) {
                $this->logger->error('Error while trying to rollback the transaction client account creation. Message: ' . $rollBackException->getMessage(), [
                    'id_client' => $client->getIdClient(),
                    'file'      => $rollBackException->getFile(),
                    'line'      => $rollBackException->getLine()
                ]);
            }
        }
    }
}
