<?php

declare(strict_types=1);

namespace Unilend\Twig;

use Exception;
use Psr\Log\LoggerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Unilend\Entity\Clients;
use Unilend\Service\Notification\NotificationDisplayManager;

class ClientExtension extends AbstractExtension
{
    /** @var NotificationDisplayManager */
    private $notificationDisplayManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param NotificationDisplayManager $notificationDisplayManager
     * @param LoggerInterface            $logger
     */
    public function __construct(
        NotificationDisplayManager $notificationDisplayManager,
        LoggerInterface $logger
    ) {
        $this->notificationDisplayManager = $notificationDisplayManager;
        $this->logger                     = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
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
                'id_client' => $client->getId(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
            ]);

            return [];
        }
    }
}
