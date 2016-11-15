<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class NotificationManager
{
    /** @var MailerManager */
    private $oMailerManager;

    public function __construct(EntityManager $oEntityManager, MailerManager $oMailerManager)
    {
        $this->oEntityManager = $oEntityManager;
        $this->oMailerManager = $oMailerManager;
    }

    /**
     * @param int        $iNotificationType
     * @param int        $iMailType
     * @param int        $iClientId
     * @param null|int   $sMailFunction
     * @param null|int   $iProjectId
     * @param null|float $fAmount
     * @param null|int   $iBidId
     * @param null|int   $iTransactionId
     */
    public function create($iNotificationType, $iMailType, $iClientId, $sMailFunction = null, $iProjectId = null, $fAmount = null, $iBidId = null, $iTransactionId = null)
    {
        /** @var \clients_gestion_notifications $oNotificationSettings */
        $oNotificationSettings = $this->oEntityManager->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->oEntityManager->getRepository('clients_gestion_mails_notif');

        $oNotification = $this->createNotification($iNotificationType, $iClientId, $iProjectId, $fAmount, $iBidId);

        if ($oNotificationSettings->getNotif($iClientId, $iMailType, 'uniquement_notif') == false) {
            if (
                (
                    $oNotificationSettings->getNotif($iClientId, $iMailType, 'immediatement') == true
                    || false === $oNotificationSettings->exist(array('id_client' => $iClientId, 'id_notif' => $iMailType))
                )
                && null !== $sMailFunction && method_exists($this->oMailerManager, $sMailFunction)
            ) {
                $this->oMailerManager->$sMailFunction($oNotification);
                $oMailNotification->immediatement = 1;
            } else {
                $oMailNotification->immediatement = 0;
            }

            $this->createEmailNotification($oNotification->id_notification, $iMailType, $iClientId, $iTransactionId, $iProjectId);
        }
    }

    /**
     * @param int $iNotificationType
     * @param int $iClientId
     * @param null|int $iProjectId
     * @param null|float $fAmount
     * @param null|int $iBidId
     * @return \notifications
     */
    public function createNotification($iNotificationType, $iClientId, $iProjectId = null, $fAmount = null, $iBidId = null)
    {
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \notifications $oNotification */
        $oNotification = $this->oEntityManager->getRepository('notifications');

        $sLenderId = '';
        if ($oLenderAccount->get($iClientId, 'id_client_owner')) {
            $sLenderId = $oLenderAccount->id_lender_account;
        }
        $oNotification->type       = $iNotificationType;
        $oNotification->id_lender  = $sLenderId;
        $oNotification->id_project = $iProjectId;
        $oNotification->amount     = $fAmount * 100;
        $oNotification->id_bid     = $iBidId;
        $oNotification->create();

        return $oNotification;
    }

    /**
     * @param int      $iNotificationId
     * @param int      $iMailType
     * @param int      $iClientId
     * @param int|null $iTransactionId
     * @param int|null $iProjectId
     * @param int|null $iLoandId
     */
    public function createEmailNotification($iNotificationId, $iMailType, $iClientId, $iTransactionId = null, $iProjectId = null, $iLoandId = null)
    {
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->oEntityManager->getRepository('clients_gestion_mails_notif');

        $oMailNotification->id_client       = $iClientId;
        $oMailNotification->id_project      = $iProjectId;
        $oMailNotification->id_notif        = $iMailType;
        $oMailNotification->date_notif      = date('Y-m-d H:i:s');
        $oMailNotification->id_notification = $iNotificationId;
        $oMailNotification->id_transaction  = $iTransactionId;
        $oMailNotification->id_loan         = $iLoandId;
        $oMailNotification->create();
    }

    public function countUnreadNotificationsForClient(\clients $oClient)
    {
        /** @var \notifications $notifications */
        $notifications  = $this->oEntityManager->getRepository('notifications');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        $lenderAccount->get($oClient->id_client, 'id_client_owner');

        return $notifications->counter('id_lender = ' . $lenderAccount->id_lender_account . ' AND status = ' . \notifications::STATUS_UNREAD);
    }

    public function generateDefaultNotificationSettings(\clients $oClient)
    {
        $aNotificationTypes = $this->getNotificationTypes();
        /** @var \clients_gestion_notifications $clientNotificationSettings */
        $clientNotificationSettings = $this->oEntityManager->getRepository('clients_gestion_notifications');

        foreach ($aNotificationTypes as $notification) {
            $clientNotificationSettings->id_client = $oClient->id_client;
            $clientNotificationSettings->id_notif  = $notification['id_client_gestion_type_notif'];

            if (in_array($notification['id_client_gestion_type_notif'],
                array(
                    \clients_gestion_type_notif::TYPE_BID_REJECTED,
                    \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT,
                    \clients_gestion_type_notif::TYPE_CREDIT_CARD_CREDIT,
                    \clients_gestion_type_notif::TYPE_DEBIT
                ))) {
                $clientNotificationSettings->immediatement = 1;
            } else {
                $clientNotificationSettings->immediatement = 0;
            }

            if (
            in_array($notification['id_client_gestion_type_notif'],
                array(
                    \clients_gestion_type_notif::TYPE_NEW_PROJECT,
                    \clients_gestion_type_notif::TYPE_BID_PLACED,
                    \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED,
                    \clients_gestion_type_notif::TYPE_REPAYMENT
                ))) {
                $clientNotificationSettings->quotidienne = 1;
            } else {
                $clientNotificationSettings->quotidienne = 0;
            }

            if ( in_array(
                $notification['id_client_gestion_type_notif'],
                array(
                    \clients_gestion_type_notif::TYPE_NEW_PROJECT,
                    \clients_gestion_type_notif::TYPE_LOAN_ACCEPTED
                ))) {
                $clientNotificationSettings->hebdomadaire = 1;
            } else {
                $clientNotificationSettings->hebdomadaire = 0;
            }

            $clientNotificationSettings->mensuelle = 0;
            $clientNotificationSettings->create();
        }
    }

    public function getNotificationTypes()
    {
        /** @var \clients_gestion_type_notif $clientNotificationTypes */
        $clientNotificationTypes = $this->oEntityManager->getRepository('clients_gestion_type_notif');
        return $clientNotificationTypes->select();
    }

}
