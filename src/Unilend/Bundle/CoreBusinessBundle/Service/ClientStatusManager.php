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
    ) {
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
        $lenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
        $lenderAccount->get($client->id_client, 'id_client_owner');

        $this->autoBidSettingsManager->off($lenderAccount);

        $client->changePassword($client->email, mt_rand());

        if ($client->status == Clients::STATUS_ONLINE) {
            $client->status = Clients::STATUS_OFFLINE;
            $client->update();
        }

        $this->addClientStatus($client, $userId, \clients_status::CLOSED_DEFINITELY, $comment);
    }

    /**
     * @param \clients|Clients $client
     *
     * @return mixed
     */
    public function getLastClientStatus($client)
    {
        if ($client instanceof \clients) {
            return $this->getLastClientStatusLegacy($client);
        }

        /** @var ClientsStatus $clientStatusEntity */
        $clientStatusEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatus')->getLastClientStatus($client->getIdClient());
        if (null !== $clientStatusEntity) {
            return $clientStatusEntity->getLabel();
        }

        return null;
    }

    /**
     * @param \clients $client
     *
     * @return mixed
     */
    private function getLastClientStatusLegacy(\clients $client)
    {
        /** @var \clients_status $clientsStatus */
        $clientsStatus        = $this->entityManagerSimulator->getRepository('clients_status');
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
        $clientStatusHistory = $this->entityManagerSimulator->getRepository('clients_status_history');
        /** @var \clients_status $lastClientStatus */
        $lastClientStatus = $this->entityManagerSimulator->getRepository('clients_status');
        $lastClientStatus->getLastStatut($client->id_client);

        switch ($lastClientStatus->status) {
            case \clients_status::COMPLETENESS:
            case \clients_status::COMPLETENESS_REMINDER:
            case \clients_status::COMPLETENESS_REPLY:
                $clientStatusHistory->addStatus(Users::USER_ID_FRONT, \clients_status::COMPLETENESS_REPLY, $client->id_client, $content);
                break;
            case \clients_status::VALIDATED:
            case \clients_status::MODIFICATION:
                $clientStatusHistory->addStatus(Users::USER_ID_FRONT, \clients_status::MODIFICATION, $client->id_client, $content);
                break;
            case \clients_status::TO_BE_CHECKED:
            default:
                $clientStatusHistory->addStatus(Users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED, $client->id_client, $content);
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

}
