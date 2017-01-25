<?php


namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ClientStatusManager
{
    /** @var  EntityManager */
    private $entityManager;
    /** @var  NotificationManager */
    private $notificationManager;
    /** @var  AutoBidSettingsManager */
    private $autoBidSettingsManager;

    /**
     * ClientStatusManager constructor.
     * @param EntityManager          $entityManager
     * @param NotificationManager    $notificationManager
     * @param AutoBidSettingsManager $autoBidSettingsManager
     */
    public function __construct(
        EntityManager $entityManager,
        NotificationManager $notificationManager,
        AutoBidSettingsManager $autoBidSettingsManager
    ) {
        $this->entityManager          = $entityManager;
        $this->notificationManager    = $notificationManager;
        $this->autoBidSettingsManager = $autoBidSettingsManager;
    }

    /**
     * @param \clients $client
     * @param int      $userId
     * @param string   $comment
     * @throws \Exception
     */
    public function closeAccount(\clients $client, $userId, $comment)
    {
        /** @var \transactions $transactions */
        $transactions = $this->entityManager->getRepository('transactions');
        if ($transactions->getSolde($client->id_client) > 0 ) {
            throw new \Exception('The client still has money in his account');
        }

        $this->notificationManager->deactivateAllNotificationSettings($client);
        $lenderAccount = $this->entityManager->getRepository('lenders_accounts');
        $lenderAccount->get($client->id_client, 'id_client_owner');

        $this->autoBidSettingsManager->off($lenderAccount);

        $client->changePassword($client->email, mt_rand());

        if ($client->status == \clients::STATUS_ONLINE) {
            $client->status = \clients::STATUS_OFFLINE;
            $client->update();
        }

        $this->addClientStatus($client, $userId, \clients_status::CLOSED_DEFINITELY, $comment);
    }

    /**
     * @param \clients $client
     * @return mixed
     */
    public function getLastClientStatus(\clients $client)
    {
        /** @var \clients_status $clientsStatus */
        $clientsStatus        = $this->entityManager->getRepository('clients_status');
        $currentClientsStatus = $clientsStatus->getLastStatut($client->id_client);

        if (is_object($currentClientsStatus) && $currentClientsStatus instanceof \clients_status) {
            return $currentClientsStatus->status;
        }

        return $clientsStatus->status;
    }

    /**
     * @param \clients $client
     * @param string   $content
     */
    public function changeClientStatusTriggeredByClientAction(\clients $client, $content)
    {
        /** @var \clients_status_history $clientStatusHistory */
        $clientStatusHistory = $this->entityManager->getRepository('clients_status_history');
        /** @var \clients_status $lastClientStatus */
        $lastClientStatus = $this->entityManager->getRepository('clients_status');
        $lastClientStatus->getLastStatut($client->id_client);

        switch ($lastClientStatus->status) {
            case \clients_status::COMPLETENESS:
            case \clients_status::COMPLETENESS_REMINDER:
            case \clients_status::COMPLETENESS_REPLY:
                $clientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::COMPLETENESS_REPLY, $client->id_client, $content);
                break;
            case \clients_status::VALIDATED:
            case \clients_status::MODIFICATION:
                $clientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::MODIFICATION, $client->id_client, $content);
                break;
            case \clients_status::TO_BE_CHECKED:
            default:
                $clientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED, $client->id_client, $content);
                break;
        }
    }

    /**
     * @param \clients    $client
     * @param int         $userId
     * @param int         $clientStatus
     * @param string|null $comment
     * @param int|null    $reminder
     */
    public function addClientStatus(\clients $client, $userId, $clientStatus, $comment = null, $reminder = null)
    {
        /** @var \clients_status_history $clientStatusHistory */
        $clientStatusHistory = $this->entityManager->getRepository('clients_status_history');
        $clientStatusHistory->addStatus($userId, $clientStatus, $client->id_client, $comment, $reminder);
    }

}
