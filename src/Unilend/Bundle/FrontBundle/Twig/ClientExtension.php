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
        return [
            [
                'id'        => 1,
                'projectId' => 1,
                'type'      => 'offer-accepted',
                'title'     => 'Offre acceptée',
                'datetime'  => (new \DateTime('2019-03-25 11:00:00')),
                'iso-8601'  => (new \DateTime('2019-03-25 11:00:00'))->format('c'),
                'content'   => 'L‘offre de participation de 20&nbsp;000&nbsp;€ que vous avez faite sur le projet «&nbsp;Éolien marin Manche&nbsp;» a été acceptée.',
                'image'     => 'circle-accepted',
                'status'    => 'unread'
            ],
            [
                'id'        => 2,
                'projectId' => 2,
                'type'      => 'offer',
                'title'     => 'Nouvelle offre',
                'datetime'  => (new \DateTime('2019-03-20 12:15:00')),
                'iso-8601'  => (new \DateTime('2019-03-20 12:15:00'))->format('c'),
                'content'   => 'CA Centre vient de faire une offre de participation de 15&nbsp;000&nbsp;€ sur votre projet «&nbsp;Région Bretagne S2 2019&nbsp;».',
                'image'     => '',
                'status'    => 'unread'
            ],
            [
                'id'        => 3,
                'projectId' => 3,
                'type'      => 'offer',
                'title'     => 'Nouveau projet',
                'datetime'  => (new \DateTime('2019-01-01 10:00:00')),
                'iso-8601'  => (new \DateTime('2019-01-01 10:00:00'))->format('c'),
                'content'   => 'Un nouveau projet de financement vous est proposé par CA Touraine-Poitou.',
                'image'     => 'project',
                'status'    => 'unread'
            ],
            [
                'id'        => 4,
                'projectId' => 4,
                'type'      => 'offer-rejected',
                'title'     => 'Offre rejetée',
                'datetime'  => (new \DateTime('2018-12-18 19:20:00')),
                'iso-8601'  => (new \DateTime('2018-12-18 19:20:00'))->format('c'),
                'content'   => '',
                'image'     => 'circle-rejected',
                'status'    => 'unread'
            ],
            [
                'id'        => 5,
                'projectId' => 5,
                'type'      => 'account',
                'title'     => 'Attente de signature',
                'datetime'  => (new \DateTime('2018-12-17 07:50:00')),
                'iso-8601'  => (new \DateTime('2018-12-17 07:50:00'))->format('c'),
                'content'   => '',
                'image'     => '',
                'status'    => 'unread'
            ],
            [
                'id'        => 6,
                'projectId' => 6,
                'type'      => 'offer',
                'title'     => 'Fin du financement',
                'datetime'  => (new \DateTime('2018-11-29 15:25:00')),
                'iso-8601'  => (new \DateTime('2018-11-29 15:25:00'))->format('c'),
                'content'   => '',
                'image'     => '',
                'status'    => 'unread'
            ],
            [
                'id'        => 7,
                'projectId' => 7,
                'type'      => 'offer',
                'title'     => 'Nouveau projet',
                'datetime'  => (new \DateTime('2018-11-27 17:45:00')),
                'iso-8601'  => (new \DateTime('2018-11-27 17:45:00'))->format('c'),
                'content'   => '',
                'image'     => 'project',
                'status'    => 'unread'
            ],
            [
                'id'        => 8,
                'projectId' => 8,
                'type'      => 'offer',
                'title'     => 'Nouvelle offre',
                'datetime'  => (new \DateTime('2018-11-17 17:44:00')),
                'iso-8601'  => (new \DateTime('2018-11-17 17:44:00'))->format('c'),
                'content'   => '',
                'image'     => '',
                'status'    => 'unread'
            ],
            [
                'id'        => 9,
                'projectId' => 9,
                'type'      => 'offer',
                'title'     => 'Nouvelle offre',
                'datetime'  => (new \DateTime('2018-11-01 11:11:00')),
                'iso-8601'  => (new \DateTime('2018-11-01 11:11:00'))->format('c'),
                'content'   => '',
                'image'     => '',
                'status'    => 'read'
            ],
            [
                'id'        => 10,
                'projectId' => 10,
                'type'      => 'offer',
                'title'     => 'Nouveau projet',
                'datetime'  => (new \DateTime('2018-10-01 14:58:00')),
                'iso-8601'  => (new \DateTime('2018-10-01 14:58:00'))->format('c'),
                'content'   => '',
                'image'     => 'project',
                'status'    => 'read'
            ]
        ];

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
