<?php

namespace Unilend\Bundle\FrontBundle\Twig;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Partner;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderManager;
use Unilend\Bundle\CoreBusinessBundle\Service\PartnerManager;
use Unilend\Bundle\FrontBundle\Service\NotificationDisplayManager;

class UserExtension extends \Twig_Extension
{
    /** @var EntityManager */
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
     * UserExtension constructor.
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
            new \Twig_SimpleFunction('lenderLevel', [$this, 'getLenderDiversificationLevel']),
            new \Twig_SimpleFunction('partner', [$this, 'getPartner']),
            new \Twig_SimpleFunction('balance', [$this, 'getBalance']),
            new \Twig_SimpleFunction('notifications', [$this, 'getLenderNotifications'])
        );
    }

    /**
     * @param Clients|null $client
     *
     * @return int
     */
    public function getLenderDiversificationLevel(?Clients $client): int
    {
        if (! $client instanceof Clients || false === $client->isLender()) {
            return 0;
        }

        try {
            $userLevel = $this->lenderManager->getDiversificationLevel($client);
        } catch (\Exception $exception) {
            $userLevel = 0;
            $this->logger->error(
                'Unable to retrieve lender diversification level', [
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
        if (! $client instanceof Clients || false === $client->isLender()) {
            return [];
        }

        try {
            $notifications = $this->notificationDisplayManager->getLastLenderNotifications($client);
        } catch (\Exception $exception) {
            $notifications = [];
            $this->logger->error(
                'Unable to retrieve last lender notifications', [
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
     * @return \Unilend\Bundle\CoreBusinessBundle\Entity\Partner
     */
    public function getPartner(Clients $client): Partner
    {
        if ($client instanceof Clients && $client->isPartner()) {
            $partner = $this->partnerManager->getPartner($client);
        }

        if (empty($partner)) {
            $partner = $this->partnerManager->getDefaultPartner();
        }

        return $partner;
    }

    /**reBusinessBundle\\Entity\\ClientsStatus::STATUS_CREATION') and app.user.notifications|length > 0 %}
     * @param Clients|null $client
     *
     * @return float
     */
    public function getBalance(?Clients $client): float
    {
        if (! $client instanceof Clients) {
            return 0;
        }

        if ($client->isLender()) {
            $walletType = WalletType::LENDER;
        } elseif ($client->isBorrower()) {
            $walletType = WalletType::BORROWER;
        } elseif ($client->isDebtCollector()) {
            $walletType = WalletType::DEBT_COLLECTOR;
        } else {
            $walletType = WalletType::PARTNER;
        }

        /** @var Wallet $wallet */
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, $walletType);

        return $wallet->getAvailableBalance();
    }
}
