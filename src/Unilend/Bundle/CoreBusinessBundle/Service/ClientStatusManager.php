<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class ClientStatusManager
{
    /** @var  EntityManagerSimulator */
    private $entityManager;
    /** @var  NotificationManager */
    private $notificationManager;
    /** @var  AutoBidSettingsManager */
    private $autoBidSettingsManager;
    /** @var  EntityManager */
    private $em;

    public function __construct(
        EntityManagerSimulator $entityManager,
        NotificationManager $notificationManager,
        AutoBidSettingsManager $autoBidSettingsManager,
        EntityManager $em
    ) {
        $this->entityManager          = $entityManager;
        $this->notificationManager    = $notificationManager;
        $this->autoBidSettingsManager = $autoBidSettingsManager;
        $this->em                     = $em;
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
        /** @var \transactions $transactions */
        $transactions = $this->entityManager->getRepository('transactions');
        if ($transactions->getSolde($client->id_client) > 0) {
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
        $clientStatusEntity = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getLastClientStatus($client->getIdClient());
        if (null !== $clientStatusEntity) {
            return $clientStatusEntity->getLabel();
        } else {
            return null;
        }
    }

    /**
     * @param \clients $client
     *
     * @return mixed
     */
    private function getLastClientStatusLegacy(\clients $client)
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
     * @param int    $iClientId
     * @param string $sContent
     */
    public function changeClientStatusTriggeredByClientAction($iClientId, $sContent)
    {
        /** @var \clients_status_history $oClientStatusHistory */
        $oClientStatusHistory = $this->entityManager->getRepository('clients_status_history');
        /** @var \clients_status $oLastClientStatus */
        $oLastClientStatus = $this->entityManager->getRepository('clients_status');
        $oLastClientStatus->getLastStatut($iClientId);

        switch ($oLastClientStatus->status) {
            case \clients_status::COMPLETENESS:
            case \clients_status::COMPLETENESS_REMINDER:
            case \clients_status::COMPLETENESS_REPLY:
                $oClientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::COMPLETENESS_REPLY, $iClientId, $sContent);
                break;
            case \clients_status::VALIDATED:
                $oClientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::MODIFICATION, $iClientId, $sContent);
                break;
            case \clients_status::TO_BE_CHECKED:
            default:
                $oClientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED, $iClientId, $sContent);
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
