<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 19/02/2016
 * Time: 15:47
 */

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

    public function create($iNotificationType, $iMailType, $iClientId, $sMailFunction = null, $iProjectId = null, $fAmount = null, $iBidId = null, $iTransactionId = null)
    {
        /** @var \notifications $oNotification */
        $oNotification = $this->oEntityManager->getRepository('notifications');
        /** @var \clients_gestion_notifications $oNotificationSettings */
        $oNotificationSettings = $this->oEntityManager->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->oEntityManager->getRepository('clients_gestion_mails_notif');

        $this->createNotification($iNotificationType, $iClientId, $iProjectId, $fAmount, $iBidId);

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
     * @param $iNotificationType
     * @param $iClientId
     * @param null|int $iProjectId
     * @param null|float $fAmount
     * @param null|int $iBidId
     * @return string
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

        return $oNotification->id_notification;
    }

    /**
     * @param $iNotificationId
     * @param $iMailType
     * @param $iClientId
     * @param $iTransactionId
     */
    public function createEmailNotification($iNotificationId, $iMailType, $iClientId, $iTransactionId, $iProjectId = null)
    {
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = $this->oEntityManager->getRepository('clients_gestion_mails_notif');

        $oMailNotification->id_client       = $iClientId;
        $oMailNotification->id_project      = $iProjectId;
        $oMailNotification->id_notif        = $iMailType;
        $oMailNotification->date_notif      = date('Y-m-d H:i:s');
        $oMailNotification->id_notification = $iNotificationId;
        $oMailNotification->id_transaction  = $iTransactionId;
        $oMailNotification->create();
    }
}
