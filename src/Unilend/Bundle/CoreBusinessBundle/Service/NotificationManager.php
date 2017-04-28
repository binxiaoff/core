<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class NotificationManager
{
    /** @var MailerManager */
    private $mailerManager;
    /** @var EntityManager  */
    private $entityManager;

    /**
     * NotificationManager constructor.
     * @param EntityManager $entityManager
     * @param MailerManager $mailerManager
     */
    public function __construct(EntityManager $entityManager, MailerManager $mailerManager)
    {
        $this->entityManager = $entityManager;
        $this->mailerManager = $mailerManager;
    }

    /**
     * @param int        $notificationType
     * @param int        $mailType
     * @param int        $clientId
     * @param null|int   $mailFunction
     * @param null|int   $projectId
     * @param null|float $amount
     * @param null|int   $bidId
     * @param null|int   $transactionId
     * @param null|int   $loanId
     */
    public function create(
        $notificationType,
        $mailType,
        $clientId,
        $mailFunction = null,
        $projectId = null,
        $amount = null,
        $bidId = null,
        $transactionId = null,
        $loanId = null
    ) {
        /** @var \clients_gestion_notifications $notificationSettings */
        $notificationSettings = $this->entityManager->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $mailNotification */
        $mailNotification = $this->entityManager->getRepository('clients_gestion_mails_notif');

        $notification = $this->createNotification($notificationType, $clientId, $projectId, $amount, $bidId);

        if ($notificationSettings->getNotif($clientId, $mailType, 'uniquement_notif') == false) {
            if (
                (
                    $notificationSettings->getNotif($clientId, $mailType, 'immediatement') == true
                    || false === $notificationSettings->exist(['id_client' => $clientId, 'id_notif'  => $mailType])
                )
                && null !== $mailFunction && method_exists($this->mailerManager, $mailFunction)
            ) {
                $this->mailerManager->$mailFunction($notification);
                $mailNotification->immediatement = 1;
            } else {
                $mailNotification->immediatement = 0;
            }

            $this->createEmailNotification($notification->id_notification, $mailType, $clientId, $transactionId, $projectId, $loanId);
        }
    }

    /**
     * @param int        $notificationType
     * @param int        $clientId
     * @param null|int   $projectId
     * @param null|float $amount
     * @param null|int   $bidId
     *
     * @return \notifications
     */
    public function createNotification($notificationType, $clientId, $projectId = null, $amount = null, $bidId = null)
    {
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManager->getRepository('lenders_accounts');
        /** @var \notifications $notification */
        $notification = $this->entityManager->getRepository('notifications');

        $lenderId = '';
        if ($lenderAccount->get($clientId, 'id_client_owner')) {
            $lenderId = $lenderAccount->id_lender_account;
        }
        $notification->type       = $notificationType;
        $notification->id_lender  = $lenderId;
        $notification->id_project = $projectId;
        $notification->amount     = bcmul($amount, 100);
        $notification->id_bid     = $bidId;
        $notification->create();

        return $notification;
    }

    /**
     * @param int      $notificationId
     * @param int      $mailType
     * @param int      $clientId
     * @param int|null $transactionId
     * @param int|null $projectId
     * @param int|null $loanId
     */
    public function createEmailNotification($notificationId, $mailType, $clientId, $transactionId = null, $projectId = null, $loanId = null)
    {
        /** @var \clients_gestion_mails_notif $mailNotification */
        $mailNotification = $this->entityManager->getRepository('clients_gestion_mails_notif');

        $mailNotification->id_client       = $clientId;
        $mailNotification->id_project      = $projectId;
        $mailNotification->id_notif        = $mailType;
        $mailNotification->date_notif      = date('Y-m-d H:i:s');
        $mailNotification->id_notification = $notificationId;
        $mailNotification->id_transaction  = $transactionId;
        $mailNotification->id_loan         = $loanId;
        $mailNotification->create();
    }

    /**
     * @param \clients $client
     * @param null|int $projectId
     *
     * @return int
     */
    public function countUnreadNotificationsForClient(\clients $client, $projectId = null)
    {
        /** @var \notifications $notifications */
        $notifications = $this->entityManager->getRepository('notifications');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManager->getRepository('lenders_accounts');
        $lenderAccount->get($client->id_client, 'id_client_owner');
        $where = 'id_lender = ' . $lenderAccount->id_lender_account . ' AND status = ' . Notifications::STATUS_UNREAD;

        if (false === empty($projectId)) {
            $where .= ' AND id_project = ' . $projectId;
        }

        return $notifications->counter($where);
    }

    /**
     * @param \clients|Clients $client
     */
    public function generateDefaultNotificationSettings($client)
    {
        if ($client instanceof Clients) {
            $clientEntity = $client;
            $client = $this->entityManager->getRepository('clients');
            $client->get($clientEntity->getIdClient());
            unset($clientEntity);
        }

        $notificationTypes = $this->getNotificationTypes();
        /** @var \clients_gestion_notifications $clientNotificationSettings */
        $clientNotificationSettings = $this->entityManager->getRepository('clients_gestion_notifications');

        foreach ($notificationTypes as $notification) {
            if ($clientNotificationSettings->exist(['id_client' => $client->id_client, 'id_notif'  => $notification['id_client_gestion_type_notif']])) {
                continue;
            }
            $clientNotificationSettings->id_client = $client->id_client;
            $clientNotificationSettings->id_notif  = $notification['id_client_gestion_type_notif'];

            $defaultImmediate = [
                \clients_gestion_type_notif::TYPE_BID_REJECTED,
                \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT,
                \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT,
                \clients_gestion_type_notif::TYPE_DEBIT
            ];

            $defaultDaily = [
                \clients_gestion_type_notif::TYPE_NEW_PROJECT,
                \clients_gestion_type_notif::TYPE_BID_PLACED,
                \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED,
                \clients_gestion_type_notif::TYPE_REPAYMENT
            ];

            $defaultWeekly = [
                \clients_gestion_type_notif::TYPE_NEW_PROJECT,
                \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED
            ];

            $clientNotificationSettings->immediatement = in_array($notification['id_client_gestion_type_notif'], $defaultImmediate) ? 1 : 0;
            $clientNotificationSettings->quotidienne   = in_array($notification['id_client_gestion_type_notif'], $defaultDaily) ? 1 : 0;
            $clientNotificationSettings->hebdomadaire  = in_array($notification['id_client_gestion_type_notif'], $defaultWeekly) ? 1 : 0;
            $clientNotificationSettings->mensuelle     = 0;
            $clientNotificationSettings->create();
        }
    }

    /**
     * @return array
     */
    public function getNotificationTypes()
    {
        /** @var \clients_gestion_type_notif $clientNotificationTypes */
        $clientNotificationTypes = $this->entityManager->getRepository('clients_gestion_type_notif');
        return $clientNotificationTypes->select();
    }

    /**
     * @param \clients $client
     */
    public function deactivateAllNotificationSettings(\clients $client)
    {
        /** @var \clients_gestion_notifications $clientNotificationSettings */
        $clientNotificationSettings = $this->entityManager->getRepository('clients_gestion_notifications');

        foreach ($clientNotificationSettings->getNotifs($client->id_client) as $idNotification => $notification){
            $clientNotificationSettings->get(['id_notif' => $idNotification]);
            $clientNotificationSettings->immediatement    = 0;
            $clientNotificationSettings->quotidienne      = 0;
            $clientNotificationSettings->hebdomadaire     = 0;
            $clientNotificationSettings->mensuelle        = 0;
            $clientNotificationSettings->uniquement_notif = 0;
            $clientNotificationSettings->update(['id_notif' => $clientNotificationSettings->id_notif]);
        }
    }

}
