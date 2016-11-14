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
     * @param string $comment
     */
    public function closeAccount(\clients $client, $comment, \users $user)
    {
        //TODO check if account is balanced at 0 otherwise it can't be closed

        $this->notificationManager->deactivateAllNotificationSettings($client);
        $lenderAccount = $this->entityManager->getRepository('lenders_accounts');
        $lenderAccount->get($client->id_client, 'id_client_owner');

        $this->autoBidSettingsManager->off($lenderAccount);

        $client->changePassword($client->email, mt_rand());

        /** @var \clients_status_history $clientStatusHistory */
        $clientStatusHistory = $this->entityManager->getRepository('clients_status_history');
        $clientStatusHistory->addStatus($user->id_user, \clients_status::CLOSED_DEFINITELY, $client->id_client, $comment);
    }

}
