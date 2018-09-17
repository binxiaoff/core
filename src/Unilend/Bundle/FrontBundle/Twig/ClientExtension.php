<?php declare(strict_types=1);

namespace Unilend\Bundle\FrontBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, Partner, Wallet, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\{LenderManager, PartnerManager};
use Unilend\Bundle\FrontBundle\Service\NotificationDisplayManager;

class ClientExtension extends \Twig_Extension
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
     *
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
    )
    {
        $this->entityManager              = $entityManager;
        $this->lenderManager              = $lenderManager;
        $this->partnerManager             = $partnerManager;
        $this->notificationDisplayManager = $notificationDisplayManager;
        $this->logger                     = $logger;
    }

    /**
     * @inheritdoc
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('lenderDiversificationLevel', [$this, 'getLenderDiversificationLevel']),
            new \Twig_SimpleFunction('partner', [$this, 'getPartner']),
            new \Twig_SimpleFunction('walletBalance', [$this, 'getBalance']),
            new \Twig_SimpleFunction('lenderNotifications', [$this, 'getLenderNotifications'])
        );
    }

    /**
     * @param Clients|null $client
     *
     * @return int
     */
    public function getLenderDiversificationLevel(?Clients $client): int
    {
        if (false === $client instanceof Clients || false === $client->isLender()) {
            return 0;
        }

        try {
            $userLevel = $this->lenderManager->getDiversificationLevel($client);
        } catch (\Exception $exception) {
            $userLevel = 0;
            $this->logger->error(
                'Unable to retrieve lender diversification level. Error: ' . $exception->getMessage(), [
                    'id_client' => $client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine()
                ]
            );
        }

        return $userLevel;
    }

    /**
     * @param Clients|null $client
     *
     * @return array
     */
    public function getLenderNotifications(?Clients $client): array
    {
        if (false === $client instanceof Clients || false === $client->isLender()) {
            return [];
        }

        try {
            $notifications = $this->notificationDisplayManager->getLastLenderNotifications($client);
        } catch (\Exception $exception) {
            $notifications = [];
            $this->logger->error(
                'Unable to retrieve last lender notifications. Error: ' . $exception->getMessage(), [
                    'id_client' => $client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine()
                ]
            );
        }

        return $notifications;
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
            $this->logger->error(
                'Cannot get the balance of the client. Unsupported client type !', [
                    'id_client' => $client->getIdClient(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__,
                ]
            );

            return 0;
        }

        /** @var Wallet $wallet */
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, $walletType);

        return (float) $wallet->getAvailableBalance();
    }
}
