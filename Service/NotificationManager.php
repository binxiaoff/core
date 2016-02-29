<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 19/02/2016
 * Time: 15:47
 */

namespace Unilend\Service;


use Unilend\core\Loader;

class NotificationManager
{
    /** @var MailerManager */
    private $oMailerManager;
    public function __construct()
    {
        $this->oMailerManager = Loader::loadService('MailerManager');
    }

    public function create($iTypeNotification, $iTypeMail, $iClientId, $sMailFunction = '', $iProjectId = '', $fAmount = '', $iBidId = '', $iTransactionId = '')
    {
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');
        /** @var \notifications $oNotification */
        $oNotification = Loader::loadData('notifications');
        /** @var \clients_gestion_notifications $oNotificationSettings */
        $oNotificationSettings = Loader::loadData('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $oMailNotification */
        $oMailNotification = Loader::loadData('clients_gestion_mails_notif');

        $iLenderId = '';
        if ($oLenderAccount->get($iClientId, 'id_client_owner')) {
            $iLenderId = $oLenderAccount->id_lender_account;
        }
        $oNotification->type       = $iTypeNotification;
        $oNotification->id_lender  = $iLenderId;
        $oNotification->id_project = $iProjectId;
        $oNotification->amount     = $fAmount * 100;
        $oNotification->id_bid     = $iBidId;
        $oNotification->create();

        if ($oNotificationSettings->getNotif($iClientId, $iTypeMail, 'uniquement_notif') == false) {
            if (($oNotificationSettings->getNotif($iClientId, $iTypeMail, 'immediatement') == true
                    || false === $oNotificationSettings->exist(array('id_client' => $iClientId, 'id_notif' => $iTypeMail)))
                && '' !== $sMailFunction) {
                $this->oMailerManager->$sMailFunction($oNotification);
                $oMailNotification->immediatement = 1;
            } else {
                $oMailNotification->immediatement = 0;
            }

            $oMailNotification->id_client       = $iClientId;
            $oMailNotification->id_notif        = $iTypeMail;
            $oMailNotification->date_notif      = date('Y-m-d H:i:s');
            $oMailNotification->id_notification = $oNotification->id_notification;
            $oMailNotification->id_transaction  = $iTransactionId;
            $oMailNotification->create();
        }
    }
}