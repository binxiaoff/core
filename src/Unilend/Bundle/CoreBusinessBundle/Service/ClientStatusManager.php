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
     * @param int $userId
     * @param string $comment
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
     * @param string $content
     */
    public function changeClientStatusTriggeredByClientAction(\clients $client, $content)
    {
        switch ($this->getLastClientStatus($client)) {
            case \clients_status::COMPLETENESS:
            case \clients_status::COMPLETENESS_REMINDER:
            case \clients_status::COMPLETENESS_REPLY:
                $this->addClientStatus($client, \users::USER_ID_FRONT, \clients_status::COMPLETENESS_REPLY, $content);
                break;
            case \clients_status::VALIDATED:
                $this->addClientStatus($client, \users::USER_ID_FRONT, \clients_status::MODIFICATION, $content);
                break;
            case \clients_status::TO_BE_CHECKED:
                $this->addClientStatus($client, \users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED, $content);
                break;
            default:
                break;
        }
    }

    /**
     * @param \clients $client
     * @param int $userId
     * @param int $clientStatus
     * @param string|null $comment
     * @param int|null $reminder
     */
    public function addClientStatus(\clients $client, $userId, $clientStatus, $comment = null, $reminder = null)
    {
        /** @var \clients_status_history $clientStatusHistory */
        $clientStatusHistory = $this->entityManager->getRepository('clients_status_history');
        $clientStatusHistory->addStatus($userId, $clientStatus, $client->id_client, $comment, $reminder);
    }

}
