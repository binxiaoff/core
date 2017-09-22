<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class ClientStatusManager
{
    /** @var  EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var  NotificationManager */
    private $notificationManager;
    /** @var  AutoBidSettingsManager */
    private $autoBidSettingsManager;
    /** @var  EntityManager */
    private $entityManager;

    /**
     * ClientStatusManager constructor.
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param NotificationManager    $notificationManager
     * @param AutoBidSettingsManager $autoBidSettingsManager
     * @param EntityManager          $entityManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        NotificationManager $notificationManager,
        AutoBidSettingsManager $autoBidSettingsManager,
        EntityManager $entityManager
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->notificationManager    = $notificationManager;
        $this->autoBidSettingsManager = $autoBidSettingsManager;
        $this->entityManager          = $entityManager;
    }

    /**
     * @param \clients $client
     * @param int      $userId
     * @param string   $comment
     *
     * @throws \Exception
     */
    public function closeAccount(\clients $client, $userId, $comment)
    {
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->id_client, WalletType::LENDER);
        if ($wallet->getAvailableBalance() > 0) {
            throw new \Exception('The client still has money in his account');
        }

        $this->notificationManager->deactivateAllNotificationSettings($client);
        $this->autoBidSettingsManager->off($wallet->getIdClient());

        $client->changePassword($client->email, mt_rand());

        if (Clients::STATUS_ONLINE == $client->status) {
            $client->status = Clients::STATUS_OFFLINE;
            $client->update();
        }

        $this->addClientStatus($client, $userId, \clients_status::CLOSED_DEFINITELY, $comment);
    }

    /**
     * @param \clients $client
     * @param string   $content
     */
    public function changeClientStatusTriggeredByClientAction(\clients $client, $content)
    {
        /** @var \clients_status_history $clientStatusHistory */
        $clientStatusHistory    = $this->entityManagerSimulator->getRepository('clients_status_history');
        $clientStatusRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatus');
        /** @var ClientsStatus $clientStatusEntity */
        $clientStatusEntity = $clientStatusRepository->getLastClientStatus($client->id_client);
        $lastStatus         = (empty($clientStatusEntity)) ? null : $clientStatusEntity->getStatus();

        switch ($lastStatus) {
            case ClientsStatus::COMPLETENESS:
            case ClientsStatus::COMPLETENESS_REMINDER:
            case ClientsStatus::COMPLETENESS_REPLY:
                $clientStatusHistory->addStatus(Users::USER_ID_FRONT, ClientsStatus::COMPLETENESS_REPLY, $client->id_client, $content);
                break;
            case ClientsStatus::VALIDATED:
            case ClientsStatus::MODIFICATION:
                $clientStatusHistory->addStatus(Users::USER_ID_FRONT, ClientsStatus::MODIFICATION, $client->id_client, $content);
                break;
            case ClientsStatus::TO_BE_CHECKED:
            default:
                $clientStatusHistory->addStatus(Users::USER_ID_FRONT, ClientsStatus::TO_BE_CHECKED, $client->id_client, $content);
                break;
        }
    }

    /**
     * @param \clients|Clients    $client
     * @param int                 $userId
     * @param int                 $clientStatus
     * @param string|null         $comment
     * @param int|null            $reminder
     */
    public function addClientStatus($client, $userId, $clientStatus, $comment = null, $reminder = null)
    {
        /** @var \clients_status_history $clientStatusHistory */
        $clientStatusHistory = $this->entityManagerSimulator->getRepository('clients_status_history');

        if ($client instanceof Clients) {
            $clientStatusHistory->addStatus($userId, $clientStatus, $client->getIdClient(), $comment, $reminder);
        }

        if ($client instanceof \clients) {
            $clientStatusHistory->addStatus($userId, $clientStatus, $client->id_client, $comment, $reminder);
        }
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function hasBeenValidatedAtLeastOnce(Clients $client)
    {
        $clientStatusHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory');
        $previousValidation  = $clientStatusHistory->getFirstClientValidation($client);

        return null !== $previousValidation;
    }
}
