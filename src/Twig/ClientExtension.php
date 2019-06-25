<?php

declare(strict_types=1);

namespace Unilend\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Unilend\Entity\{Clients, Partner, Wallet, WalletType};
use Unilend\Service\Front\NotificationDisplayManager;
use Unilend\Service\{LenderManager, PartnerManager};

class ClientExtension extends AbstractExtension
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LenderManager */
    private $lenderManager;
    /** @var PartnerManager */
    private $partnerManager;
    /** @var NotificationDisplayManager */
    private $notificationDisplayManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface     $entityManager
     * @param LenderManager              $lenderManager
     * @param PartnerManager             $partnerManager
     * @param NotificationDisplayManager $notificationDisplayManager
     * @param LoggerInterface            $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LenderManager $lenderManager,
        PartnerManager $partnerManager,
        NotificationDisplayManager $notificationDisplayManager,
        LoggerInterface $logger
    ) {
        $this->entityManager              = $entityManager;
        $this->lenderManager              = $lenderManager;
        $this->partnerManager             = $partnerManager;
        $this->notificationDisplayManager = $notificationDisplayManager;
        $this->logger                     = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('partner', [$this, 'getPartner']),
            new TwigFunction('walletBalance', [$this, 'getBalance']),
            new TwigFunction('notifications', [$this, 'getNotifications']),
        ];
    }

    /**
     * @param Clients|null $client
     *
     * @throws Exception
     *
     * @return array
     */
    public function getNotifications(?Clients $client): array
    {
        if (false === $client instanceof Clients) {
            return [];
        }

        try {
            return $this->notificationDisplayManager->getLastClientNotifications($client);
        } catch (Exception $exception) {
            $this->logger->error('Unable to retrieve last client notifications. Error: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
            ]);

            return [];
        }
    }

    /**
     * @param Clients|null $client
     *
     * @return Partner
     */
    public function getPartner(Clients $client): Partner
    {
        if ($client instanceof Clients && $client->isPartner()) {
            $partner = $this->partnerManager->getPartner($client);
        }

        return $partner ?? $this->partnerManager->getDefaultPartner();
    }

    /**
     * @param Clients|null $client
     *
     * @return float
     */
    public function getBalance(?Clients $client): float
    {
        if (false === $client instanceof Clients) {
            return 0;
        }

        if ($client->isLender()) {
            $walletType = WalletType::LENDER;
        } elseif ($client->isBorrower()) {
            $walletType = WalletType::BORROWER;
        } elseif ($client->isDebtCollector()) {
            $walletType = WalletType::DEBT_COLLECTOR;
        } elseif ($client->isPartner()) {
            $walletType = WalletType::PARTNER;
        } else {
            $this->logger->error('Cannot get the balance of the client. Unsupported client type !', [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
            ]);

            return 0;
        }

        /** @var Wallet $wallet */
        $wallet = $this->entityManager->getRepository(Wallet::class)->getWalletByType($client, $walletType);

        return (float) $wallet->getAvailableBalance();
    }
}
