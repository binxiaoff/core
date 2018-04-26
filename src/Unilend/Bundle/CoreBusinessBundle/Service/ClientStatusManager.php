<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, ClientsStatus, ClientsStatusHistory, Users, WalletType
};

class ClientStatusManager
{
    /** @var NotificationManager */
    private $notificationManager;
    /** @var AutoBidSettingsManager */
    private $autoBidSettingsManager;
    /** @var EntityManager */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param NotificationManager    $notificationManager
     * @param AutoBidSettingsManager $autoBidSettingsManager
     * @param EntityManager          $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(
        NotificationManager $notificationManager,
        AutoBidSettingsManager $autoBidSettingsManager,
        EntityManager $entityManager,
        LoggerInterface $logger
    )
    {
        $this->notificationManager    = $notificationManager;
        $this->autoBidSettingsManager = $autoBidSettingsManager;
        $this->entityManager          = $entityManager;
        $this->logger                 = $logger;
    }

    /**
     * @param \clients $client
     * @param int      $userId
     * @param string   $comment
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function closeLenderAccount(\clients $client, $userId, $comment): void
    {
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->id_client, WalletType::LENDER);
        if ($wallet->getAvailableBalance() > 0) {
            throw new \Exception('The client still has money in his account');
        }

        $this->notificationManager->deactivateAllNotificationSettings($client);
        $this->autoBidSettingsManager->off($wallet->getIdClient());

        $client->changePassword($client->email, mt_rand());

        $this->addClientStatus($client, $userId, ClientsStatus::STATUS_CLOSED_DEFINITELY, $comment);
    }

    /**
     * @param Clients     $client
     * @param string|null $content
     */
    public function changeClientStatusTriggeredByClientAction(Clients $client, ?string $content = null): void
    {
        switch ($client->getIdClientStatusHistory()->getIdStatus()->getId()) {
            case ClientsStatus::STATUS_COMPLETENESS:
            case ClientsStatus::STATUS_COMPLETENESS_REMINDER:
            case ClientsStatus::STATUS_COMPLETENESS_REPLY:
                $status = ClientsStatus::STATUS_COMPLETENESS_REPLY;
                break;
            case ClientsStatus::STATUS_VALIDATED:
            case ClientsStatus::STATUS_MODIFICATION:
                $status = ClientsStatus::STATUS_MODIFICATION;
                break;
            case ClientsStatus::STATUS_CREATION:
                $status = ClientsStatus::STATUS_CREATION;
                break;
            case ClientsStatus::STATUS_TO_BE_CHECKED:
            default:
                $status = ClientsStatus::STATUS_TO_BE_CHECKED;
                break;
        }

        $this->addClientStatus($client, Users::USER_ID_FRONT, $status, $content);
    }

    /**
     * @param Clients|\clients $client
     * @param int              $userId
     * @param int              $status
     * @param string|null      $comment
     * @param int|null         $reminder
     */
    public function addClientStatus($client, int $userId, int $status, ?string $comment = null, ?int $reminder = null): void
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        if (
            $client->getIdClientStatusHistory()
            && $status === $client->getIdClientStatusHistory()->getIdStatus()->getId()
            && empty($comment)
            && empty($reminder)
        ) {
            return;
        }

        $this->entityManager->getConnection()->beginTransaction();

        try {
            $clientStatus = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatus')->find($status);
            $user         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find($userId);

            $clientStatusHistory = new ClientsStatusHistory();
            $clientStatusHistory
                ->setIdClient($client)
                ->setIdStatus($clientStatus)
                ->setIdUser($user)
                ->setContent($comment)
                ->setNumeroRelance($reminder);

            $this->entityManager->persist($clientStatusHistory);
            $this->entityManager->flush($clientStatusHistory);

            $client->setIdClientStatusHistory($clientStatusHistory);
            $this->entityManager->flush($client);

            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->logger->error(
                'Error while changing client status. Message: ' . $exception->getMessage(),
                ['id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );

            try {
                $this->entityManager->getConnection()->rollBack();
            } catch (ConnectionException $rollBackException) {
                $this->logger->error(
                    'Error while trying to rollback the transaction client status update. Message: ' . $rollBackException->getMessage(),
                    ['id_client' => $client->getIdClient(), 'file' => $rollBackException->getFile(), 'line' => $rollBackException->getLine()]
                );
            }
        }
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function hasBeenValidatedAtLeastOnce(Clients $client): bool
    {
        $clientStatusHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory');
        $previousValidation  = $clientStatusHistory->getFirstClientValidation($client);

        return null !== $previousValidation;
    }
}
