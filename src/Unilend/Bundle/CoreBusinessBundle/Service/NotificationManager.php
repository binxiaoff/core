<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    ClientsGestionMailsNotif, ClientsGestionTypeNotif, WalletBalanceHistory, WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class NotificationManager
{
    /** @var MailerManager */
    private $mailerManager;
    /** @var  EntityManager */
    private $entityManager;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /**
     * NotificationManager constructor.
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param EntityManager $entityManager
     * @param MailerManager $mailerManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        MailerManager $mailerManager
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->mailerManager          = $mailerManager;
    }

    /**
     * @param int                       $notificationType
     * @param int                       $mailType
     * @param int                       $clientId
     * @param null|int                  $mailFunction
     * @param null|int                  $projectId
     * @param null|float                $amount
     * @param null|int                  $bidId
     * @param null|WalletBalanceHistory $walletBalanceHistory
     * @param null|int                  $loanId
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function create(
        $notificationType,
        $mailType,
        $clientId,
        $mailFunction = null,
        $projectId = null,
        $amount = null,
        $bidId = null,
        WalletBalanceHistory $walletBalanceHistory = null,
        $loanId = null
    ) {
        /** @var \clients_gestion_notifications $notificationSettings */
        $notificationSettings = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');
        $notification         = $this->createNotification($notificationType, $clientId, $projectId, $amount, $bidId);

        if ($notificationSettings->getNotif($clientId, $mailType, 'uniquement_notif') == false) {
            if (
                $notificationSettings->getNotif($clientId, $mailType, 'immediatement')
                || false === $notificationSettings->exist(['id_client' => $clientId, 'id_notif'  => $mailType])
                && null !== $mailFunction && method_exists($this->mailerManager, $mailFunction)
            ) {
                $this->mailerManager->$mailFunction($notification);
                $sent = true;
            } else {
                $sent = false;
            }

            $this->createEmailNotification($notification->id_notification, $mailType, $clientId, $walletBalanceHistory, $projectId, $loanId, $sent);
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
        /** @var \notifications $notification */
        $notification = $this->entityManagerSimulator->getRepository('notifications');
        $wallet       = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($clientId, WalletType::LENDER);

        $lenderId = '';
        if (null !== $wallet) {
            $lenderId = $wallet->getId();
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
     * @param int|null                  $notificationId
     * @param int                       $mailType
     * @param int                       $clientId
     * @param WalletBalanceHistory|null $walletBalanceHistory
     * @param int|null                  $projectId
     * @param int|null                  $loanId
     * @param bool                      $sent
     * @param \DateTime|null            $notificationDate
     *
     * @return ClientsGestionMailsNotif
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createEmailNotification(
        int $notificationId = null,
        int $mailType,
        int $clientId,
        WalletBalanceHistory $walletBalanceHistory = null,
        int $projectId = null,
        int $loanId = null,
        bool $sent = false,
        \DateTime $notificationDate = null
    ) : ClientsGestionMailsNotif
    {
        $emailNotification = new ClientsGestionMailsNotif();
        $emailNotification
            ->setIdClient($clientId)
            ->setIdNotif($mailType)
            ->setDateNotif(new \DateTime('NOW'))
            ->setIdNotification($notificationId)
            ->setIdProject($projectId)
            ->setIdLoan($loanId);

        if ($notificationDate instanceof \DateTime) {
            $emailNotification->setDateNotif($notificationDate);
        }

        if (null !== $walletBalanceHistory) {
            $emailNotification->setIdWalletBalanceHistory($walletBalanceHistory);
        }

        if ($sent) {
            $emailNotification->setImmediatement(1);
        }

        $this->entityManager->persist($emailNotification);
        $this->entityManager->flush($emailNotification);

        return $emailNotification;
    }

    /**
     * @param int $clientId
     */
    public function generateDefaultNotificationSettings(int $clientId)
    {
        $clientsGestionTypeNotifRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsGestionTypeNotif');
        /** @var \clients_gestion_notifications $clientNotificationSettings */
        $clientNotificationSettings = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');

        foreach ($clientsGestionTypeNotifRepository->findAll() as $notificationType) {
            $idNotificationType = $notificationType->getIdClientGestionTypeNotif();

            if ($clientNotificationSettings->exist(['id_client' => $clientId, 'id_notif' => $idNotificationType])) {
                continue;
            }

            $defaultImmediate = [
                ClientsGestionTypeNotif::TYPE_NEW_PROJECT,
                ClientsGestionTypeNotif::TYPE_BID_REJECTED,
                ClientsGestionTypeNotif::TYPE_BANK_TRANSFER_CREDIT,
                ClientsGestionTypeNotif::TYPE_CREDIT_CARD_CREDIT,
                ClientsGestionTypeNotif::TYPE_DEBIT
            ];

            $defaultDaily = [
                ClientsGestionTypeNotif::TYPE_BID_PLACED,
                ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED,
                ClientsGestionTypeNotif::TYPE_REPAYMENT
            ];

            $defaultWeekly = [
                ClientsGestionTypeNotif::TYPE_NEW_PROJECT,
                ClientsGestionTypeNotif::TYPE_LOAN_ACCEPTED
            ];

            $clientNotificationSettings->id_client     = $clientId;
            $clientNotificationSettings->id_notif      = $idNotificationType;
            $clientNotificationSettings->immediatement = in_array($idNotificationType, $defaultImmediate) ? 1 : 0;
            $clientNotificationSettings->quotidienne   = in_array($idNotificationType, $defaultDaily) ? 1 : 0;
            $clientNotificationSettings->hebdomadaire  = in_array($idNotificationType, $defaultWeekly) ? 1 : 0;
            $clientNotificationSettings->mensuelle     = 0;
            $clientNotificationSettings->create();
        }
    }

    /**
     * @param \clients $client
     */
    public function deactivateAllNotificationSettings(\clients $client)
    {
        /** @var \clients_gestion_notifications $clientNotificationSettings */
        $clientNotificationSettings = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');

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
